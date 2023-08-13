<?php

namespace PHPCore\Changelly\Fiat;

class RequestType
{
	protected string $methodName;
	protected string $requestMethod;

	public function __construct(string $methodName, string $requestMethod)
	{
		$this->methodName = $methodName;
		$this->requestMethod = $requestMethod;
	}

	/**
	 * @return string
	 */
	public function getMethodName(): string
	{
		return $this->methodName;
	}

	/**
	 * @return string
	 */
	public function getRequestMethod(): string
	{
		return $this->requestMethod;
	}
}
