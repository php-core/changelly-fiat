# changelly
Changelly.com PHP SDK


---

### This library requires min. PHP 8.0

# Installation

``
composer require php-core/changelly-fiat
``

# Examples
### Get number

```
// require_once './vendor/autoload.php';
```

## Authentication 

### Option 1
Have $_ENV['CHANGELLY_PUBLIC_KEY'] and $_ENV['CHANGELLY_PRIVATE_KEY'] set to the actual keys

### Option 2

```
// Replace 'YOUR_PUBLIC_KEY' with your actual API key from Changelly.
$publicKey = 'YOUR_PUBLIC_KEY';

// Replace PATH_TO_PRIVATE_KEY with the actual full path to a file storing your private key from Changelly. 
$privateKey = '/full/path/to/private.key';

PHPCore\Changelly\Fiat\Api::init($publicKey, $privateKey);
```


## Usage

```

$fiat = 'USD';
$crypto = 'BTC';
$amount = 50;
$countryCode = 'EE';
$stateCode = ''; // use US state code if country code is US
$userId = '567890';
$ipAddress = 'USER_IP'; // replace with user IP address

try {
	// Example: Getting a number for a specific service and country.
    $offers = PHPCore\Changelly\Fiat\Api::getOffers(
        $fiat, $crypto,
	    $amount, $countryCode, null,
		$userId, $stateCode, $ipAddress
    );	
        
    print_r($offers);
       
} catch (Exception $e) {
	echo "Error: " . $e->getMessage() . "\n";
}
```
---
### Other examples can be found [here](https://github.com/php-core/changelly-fiat/blob/main/examples/all.php)

---
## For more, visit the [Official changelly Fiat API Documentation](https://changelly.com/new_theme_api.html)

---

## License
This project is licensed under the MIT License.
