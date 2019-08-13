<?php
namespace Mvaessen\KrakenFutureApi\Tests;

use Dotenv\Dotenv;
use Mvaessen\KrakenFutureApi\KrakenFutureApiException;
use Mvaessen\KrakenFutureApi\Client;
use PHPUnit\Framework\TestCase;

class BasicApiTest extends TestCase
{
    protected $api;

    protected function setUp()
    {
        $dotenv = new Dotenv(__DIR__ . '/..');
        $dotenv->load();

        $this->api = new Client(
            getenv('KRAKEN_FUTURE_API_ID'),
            getenv('KRAKEN_FUTURE_API_SECRET'),
            $this->getUrl()
        );
    }

    private function getUrl()
    {
        $override = getenv('KRAKEN_FUTURE_API_URL');

        if($override) {
            return $override;
        }

        return 'https://conformance.cryptofacilities.com/derivatives';
    }

    public function testAccounts()
    {
        $response = $this->api->queryPrivate('get', 'accounts');

        $this->assertTrue($response['result'] == 'success');
    }

    public function testInstruments()
    {
        $response = $this->api->queryPrivate('get', 'instruments');

        $this->assertTrue(isset($response['instruments']));
    }

    public function testOpenAndCloseOrder()
    {
        $response1 = $this->api->queryPrivate('post', 'sendorder', [
            'orderType' => 'ioc',
            'symbol' => 'pi_xbtusd',
            'side' => 'sell',
            'size' => 10,
            'limitPrice' => 10000
        ]);

        sleep(1);

        $response2 = $this->api->queryPrivate('post', 'sendorder', [
            'orderType' => 'ioc',
            'symbol' => 'pi_xbtusd',
            'side' => 'buy',
            'size' => 10,
            'limitPrice' => 100000
        ]);

        $this->assertTrue(
            (
                isset($response1['result']) and
                $response1['result'] == 'success'
            ) and
            (
                isset($response2['result']) and
                $response2['result'] == 'success'
            )
        );
    }

    public function testOpenAndCloseOnlyOrder()
    {
        $response1 = $this->api->queryPrivate('post', 'sendorder', [
            'orderType' => 'ioc',
            'symbol' => 'pi_xbtusd',
            'side' => 'sell',
            'size' => 10,
            'limitPrice' => 10000
        ]);

        sleep(1);

        $response2 = $this->api->queryPrivate('post', 'sendorder', [
            'orderType' => 'ioc',
            'symbol' => 'pi_xbtusd',
            'side' => 'buy',
            'size' => 100,
            'limitPrice' => 100000,
            'reduceOnly' => 'true'
        ]);

        $this->assertTrue(
            (
                isset($response1['result']) and
                $response1['result'] == 'success'
            ) and
            (
                isset($response2['result']) and
                $response2['result'] == 'success'
            )
        );
    }

    public function testSmallOrder()
    {
        $fail = true;

        try {
            $this->api->queryPrivate('post', 'sendorder', [
                'orderType' => 'ioc',
                'symbol' => 'pi_xbtusd',
                'side' => 'buy',
                'size' => '-1',
                'limitPrice' => 1
            ]);
        } catch(KrakenFutureApiException $e) {
            $this->assertContains('invalidArgument: -1', $e->getMessage());
            $fail = false;
        }

        $this->assertFalse($fail);
    }
}