<?php

namespace PHPCore\Changelly\Fiat;

use PHPCore\Changelly\Fiat\Exceptions\ChangellyApiException;
use PHPCore\Changelly\Fiat\Exceptions\ChangellyInvalidArgumentsException;
use PHPCore\Changelly\Fiat\Exceptions\ChangellyInvalidResponseException;
use PHPCore\Changelly\Fiat\Exceptions\ChangellySignatureException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use ReflectionException;

class Api
{
    public static function init(string $publicKey, string $privateKey): void
    {
        $_ENV['CHANGELLY_PUBLIC_KEY'] = $publicKey;
        $_ENV['CHANGELLY_PRIVATE_KEY'] = $privateKey;
    }

    /**
     * @throws ChangellyApiException
     * @throws ChangellyInvalidResponseException
     * @throws ChangellySignatureException
     * @throws ChangellyInvalidArgumentsException
     * @throws \Exception
     */
	public static function getOffers(
		string $currencyFrom,
		string $currencyTo,
		string $amountFrom,
		string $country,
		?string $providerCode = null,
		?string $externalUserId = null,
		?string $state = null,
		?string $ip = null
	): array
	{
		$tmpOffers = self::sendRequest(
			'offers',
			$currencyFrom,
			$currencyTo,
			$amountFrom,
			$country,
			$providerCode,
			$externalUserId,
			$state,
			$ip
		);
		$offers = [];

		foreach ($tmpOffers as $tmpOffer) {
			if (
				!empty($tmpOffer['paymentMethodOffer'])
			) {
				foreach ($tmpOffer['paymentMethodOffer'] as $paymentMethodOffer) {
					if (!empty($paymentMethodOffer['method']) && $paymentMethodOffer['method'] === 'card') {
						$offer = array_merge($tmpOffer, $paymentMethodOffer);
						$offer['amountExpectedFiat'] = (new \iSecPay\CryptoExchange\Api())->convert($currencyFrom, $currencyTo, (float)$offer['amountExpectedTo']);
						unset($offer['paymentMethodOffer']);
					}
				}
			}
			if (!empty($offer)) {
				$offers[] = $offer;
			}
		}

		return $offers;
	}

	/**
	 * @throws ChangellyApiException
	 * @throws ChangellyInvalidResponseException
	 * @throws ChangellySignatureException
	 * @throws ChangellyInvalidArgumentsException
	 */
	public static function getProviders(): array
	{
		return self::sendRequest('providers');
	}

	/**
	 * @throws ChangellyApiException
	 * @throws ChangellyInvalidResponseException
	 * @throws ChangellySignatureException
	 * @throws ChangellyInvalidArgumentsException
	 */
	public static function postOrder(
		string $walletAddress,
		string $externalOrderId,
		string $externalUserId,
		string $providerCode,
		string $currencyFrom,
		string $currencyTo,
		string $amountFrom,
		string $paymentMethod,
		string $country,
		?string $state = null,
		?string $ip = null,
		?string $userAgent = null,
	): array
	{
		return self::sendRequest(
			'orders',
			$walletAddress,
			$externalOrderId,
			$externalUserId,
			$providerCode,
			$currencyFrom,
			$currencyTo,
			$amountFrom,
			$paymentMethod,
			$country,
			$state,
			$ip,
			$userAgent,
		);
	}

	/**
	 * @throws ChangellyApiException
	 * @throws ChangellySignatureException
	 * @throws ChangellyInvalidResponseException
	 * @throws ChangellyInvalidArgumentsException
	 */
	private static function sendRequest(string $action, ...$args): array
	{
		$endpoint = 'https://fiat-api.changelly.com/v1/' . $action;

		$requestTypes = [
			'offers' => new RequestType('getOffers', 'get'),
			'providers' => new RequestType('getProviders', 'get'),
			'orders' => new RequestType('postOrder', 'post'),
		];

		$requestType = $requestTypes[$action];
		$method = $requestType->getMethodName();
		$requestMethod = $requestType->getRequestMethod();

		try {
			$m = new \ReflectionMethod(Api::class, $method);
		} catch (ReflectionException $e) {
			throw new ChangellyInvalidArgumentsException($e->getMessage());
		}

		$payload = [];
		$i = 0;
		foreach ($m->getParameters() as $param) {
			if (!empty($args[$i])) {
				$payload[$param->getName()] = $args[$i];
			}
			$i++;
		}

		try {
			if ($requestMethod === 'get') {
				$uri = $endpoint . (empty($payload) ? '' : '?' . http_build_query($payload));
				$response = (new Client())->get($uri, [
					'headers' => [
						'X-Api-Key' => $_ENV['CHANGELLY_PUBLIC_KEY'],
						'X-Api-Signature' => self::signApiPayload($uri, []),
					],
				]);
			} else {
				$response = (new Client())->post($endpoint, [
					'headers' => [
						'X-Api-Key' => $_ENV['CHANGELLY_PUBLIC_KEY'],
						'X-Api-Signature' => self::signApiPayload($endpoint, $payload),
					],
					RequestOptions::JSON => $payload,
				]);
			}
		} catch (GuzzleException $exception) {
			throw new ChangellyApiException($exception->getResponse()->getBody()->getContents());
		}

		if (
			empty($response)
			|| empty($body = $response->getBody())
			|| empty($contents = $body->getContents())
			|| empty($data = json_decode($contents, true))
		) {
			throw new ChangellyInvalidResponseException();
		}
		return $data;
	}

    /**
     * @throws ChangellySignatureException
     */
    private static function signApiPayload(string $endpoint, array $payload): string
    {
        $privateKey = openssl_get_privatekey($_ENV['CHANGELLY_PRIVATE_KEY']);
        if (!$privateKey) {
            throw new ChangellySignatureException('Invalid private key: ' . openssl_error_string());
        }
        $realPayload = $endpoint . json_encode($payload, JSON_FORCE_OBJECT);
        $signature = null;
        if (
            !openssl_sign($realPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256)
            || empty($signature)
        ) {
            throw new ChangellySignatureException('Invalid API payload');
        }
        return base64_encode($signature);
    }
}
