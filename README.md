# Kraken Futures API Wrapper PHP
Simple Kraken Futures API Wrapper, written in PHP. Includes some basic methods to work with the API and assumes you know your way around. Check out the [Kraken Futures API Documentation](https://support.kraken.com/hc/en-us/categories/360001806372-Futures-API) for more information about the available endpoints. Will throw an exception when errors are encountered.

### Please note:
- Does NOT include mechanism to intercept rate limit.
- Comes without any support.
- Use at your own risk.

#### Getting started
`composer require mvaessen/binance-api`
```php
require 'vendor/autoload.php';
$api = new Mvaessen\KrakenFutureApi\Client('<api key>','<secret>');
```


##### Public endpoint call
```php
$result = $api->queryPublic('<method>', '<endpoint>', '<request>');
```

##### Private endpoint call
```php
$result = $api->queryPrivate('<method>', '<endpoint>', '<request>');
```

##Testing
Make sure to populate the .env file, with your testing API keys. Afterwards run `./vendor/bin/phpunit tests/BasicApiTest.php`.

## Extending & custom error reporting
You can choose to overwrite the `processErrorCode` and `processException` methods to report the errors to your favorite bugreporting software.

```
<?php
namespace App;

use Mvaessen\KrakenFutureApi\KrakenFutureApiException;
use Mvaessen\KrakenFutureApi\Client;

class KrakenFutureApi extends Client
{
   public function accounts()
   {
        return $this->api->queryPrivate('get', 'accounts');
   }

   protected function processErrorCode($response, $method, $url, $request)
   {
       //todo report to bugtracking software

       throw new KrakenFutureApiException($response['msg']);
   }
   
   protected function processException($e, $method, $url, $request)
   {
       //todo report to bugtracking software

       throw new KrakenFutureApiException($e->getMessage());
   }
}
```