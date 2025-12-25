# ClubKash VTU PHP Package

[![Run Tests](https://github.com/henryejemuta/php-clubkash-vtu/actions/workflows/run-tests.yml/badge.svg)](https://github.com/henryejemuta/php-clubkash-vtu/actions/workflows/run-tests.yml)
[![Latest Stable Version](https://poser.pugx.org/henryejemuta/php-clubkash-vtu/v/stable)](https://packagist.org/packages/henryejemuta/php-clubkash-vtu)
[![Total Downloads](https://poser.pugx.org/henryejemuta/php-clubkash-vtu/downloads)](https://packagist.org/packages/henryejemuta/php-clubkash-vtu)
[![License](https://poser.pugx.org/henryejemuta/php-clubkash-vtu/license)](https://packagist.org/packages/henryejemuta/php-clubkash-vtu)

A robust PHP package for integrating with the ClubKash (Nellobytes Systems) VTU API. This package allows you to easily purchase airtime, data, cable TV, and electricity tokens.

## Features

-   **Airtime Purchase**: Buy airtime for all major Nigerian networks.
-   **Data Purchase**: Buy data bundles for all major Nigerian networks.
-   **Cable TV Subscription**: Subscribe to DSTV, GOTV, and Startimes.
-   **Electricity Bill Payment**: Pay for prepaid and postpaid electricity meters.
-   **Wallet Balance**: Check your wallet balance.
-   **Universal Compatibility**: Works with Laravel, CodeIgniter, Symfony, and raw PHP projects.

## Installation

You can install the package via composer:

```bash
composer require henryejemuta/php-clubkash-vtu
```

## Usage

### Initialization

To start using the package, initialize the `Client` with your UserID, API Key, and optional configuration.

```php
use HenryEjemuta\Clubkash\Client;

$config = [
    'timeout' => 30, // Optional: Request timeout in seconds
];

$client = new Client('YOUR_USER_ID', 'YOUR_API_KEY', $config);
```

### Check Wallet Balance

```php
try {
    $balance = $client->getWalletBalance();
    print_r($balance);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Airtime Purchase

```php
// Network Codes: 01=MTN, 02=GLO, 03=9Mobile, 04=Airtel
$response = $client->purchaseAirtime('01', 100.00, '08012345678');
print_r($response);
```

### Data Purchase

```php
// Data Plan Code: Get from ClubKash Documentation or use verify tool
$response = $client->purchaseData('01', 'DATA_PLAN_CODE', '08012345678');
print_r($response);
```

### Cable TV Subscription

```php
// CableTV: 'dstv', 'gotv', 'startimes'
$response = $client->purchaseCableTV('dstv', 'package_code', 'IUC_NUMBER', 'PHONE_NUMBER');
print_r($response);
```

### Electricity Payment

```php
// ElectricCompany: '01', '02', etc. MeterType: '01' (Prepaid), '02' (Postpaid)
$response = $client->purchaseElectricity('01', 'METER_NUMBER', '01', 1000.00, 'PHONE_NUMBER');
print_r($response);
```

## API Documentation

For full API documentation, please refer to the [Nellobytes Systems API](https://www.nellobytesystems.com/).

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
