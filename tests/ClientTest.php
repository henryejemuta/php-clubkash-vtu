<?php

namespace HenryEjemuta\Clubkash\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use HenryEjemuta\Clubkash\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private $container = [];
    private $mockHandler;
    private $userID = 'test_user';
    private $apiKey = 'test_key';

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $history = Middleware::history($this->container);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);
        
        // We need to inject this handler into the client. 
        // Our Client accepts a 'handler' or 'handler_stack' in config if we programmed it right, 
        // or we can pass the guzzle client instance if we refactor allowed injection.
        // Looking at my Client::__construct, it takes $config which is passed to Guzzle.
        // So I can pass 'handler' => $handlerStack
    }

    private function createClient(array $config = []): Client
    {
        $handlerStack = HandlerStack::create($this->mockHandler);
        $history = Middleware::history($this->container);
        $handlerStack->push($history);
        
        $config['handler'] = $handlerStack;
        
        return new Client($this->userID, $this->apiKey, $config);
    }

    public function testGetWalletBalance()
    {
        $client = $this->createClient();
        
        // Mock response
        $this->mockHandler->append(new Response(200, [], json_encode(['balance' => 500.00, 'currency' => 'NGN'])));

        $result = $client->getWalletBalance();

        $this->assertIsArray($result);
        $this->assertEquals(500.00, $result['balance']);

        // Verify request parameters
        $transaction = $this->container[0];
        $request = $transaction['request'];
        
        $this->assertEquals('GET', $request->getMethod());
        $this->assertStringContainsString('UserID=test_user', $request->getUri()->getQuery());
        $this->assertStringContainsString('APIKey=test_key', $request->getUri()->getQuery());
        $this->assertStringContainsString('APIWalletBalanceV1.asp', $request->getUri()->getPath());
    }

    public function testPurchaseAirtime()
    {
        $client = $this->createClient();
        $this->mockHandler->append(new Response(200, [], json_encode(['status' => 'ORDER_RECEIVED'])));

        $result = $client->purchaseAirtime('01', 100.00, '08012345678');

        $this->assertIsArray($result);
        $this->assertEquals('ORDER_RECEIVED', $result['status']);

        $request = $this->container[0]['request'];
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('MobileNetwork=01', $query);
        $this->assertStringContainsString('Amount=100', $query);
        $this->assertStringContainsString('MobileNumber=08012345678', $query);
        $this->assertStringContainsString('RequestID=', $query); // Check RequestID is present
    }

    public function testPurchaseData()
    {
        $client = $this->createClient();
        $this->mockHandler->append(new Response(200, [], json_encode(['status' => 'ORDER_RECEIVED'])));

        $result = $client->purchaseData('01', 'DATA_PLAN_1GB', '08012345678', 'MY_REQ_ID');

        $request = $this->container[0]['request'];
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('MobileNetwork=01', $query);
        $this->assertStringContainsString('DataPlan=DATA_PLAN_1GB', $query);
        $this->assertStringContainsString('RequestID=MY_REQ_ID', $query);
    }

    public function testPurchaseCableTV()
    {
        $client = $this->createClient();
        $this->mockHandler->append(new Response(200, [], json_encode(['status' => 'ORDER_RECEIVED'])));

        $client->purchaseCableTV('dstv', 'dstv_padi', '1234567890', '08012345678');

        $request = $this->container[0]['request'];
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('CableTV=dstv', $query);
        $this->assertStringContainsString('Package=dstv_padi', $query);
        $this->assertStringContainsString('SmartCardNo=1234567890', $query);
    }

    public function testAuthenticate()
    {
        $client = $this->createClient();
        $this->mockHandler->append(new Response(200, [], json_encode(['token' => 'NEW_ACCESS_TOKEN', 'expires_in' => 604800]))); // 7 days

        $result = $client->authenticate();

        $this->assertIsArray($result);
        $this->assertEquals('NEW_ACCESS_TOKEN', $result['token']);

        // Verify request was made to APIToken.asp (or whatever we chose)
        $request = $this->container[0]['request'];
        $this->assertStringContainsString('APIToken.asp', $request->getUri()->getPath());
    }

    public function testRequestWithToken()
    {
        // Initialize client WITH a token
        $client = $this->createClient(['token' => 'EXISTING_TOKEN']);
        
        $this->mockHandler->append(new Response(200, [], json_encode(['balance' => 500.00])));

        $client->getWalletBalance();

        $request = $this->container[0]['request'];
        
        // Verify Authorization header is present
        $this->assertTrue($request->hasHeader('Authorization'));
        $this->assertEquals('Bearer EXISTING_TOKEN', $request->getHeaderLine('Authorization'));
        
        // Verify UserID/APIKey are NOT in query params (since we prioritize token)
        $query = $request->getUri()->getQuery();
        $this->assertStringNotContainsString('UserID=', $query);
        $this->assertStringNotContainsString('APIKey=', $query);
    }

    public function testPurchaseElectricity()
    {
        $client = $this->createClient();
        $this->mockHandler->append(new Response(200, [], json_encode(['status' => 'ORDER_RECEIVED'])));

        $client->purchaseElectricity('01', '1234567890', '01', 1000.00, '08012345678');

        $request = $this->container[0]['request'];
        $query = $request->getUri()->getQuery();
        $this->assertStringContainsString('ElectricCompany=01', $query);
        $this->assertStringContainsString('MeterNo=1234567890', $query);
        $this->assertStringContainsString('MeterType=01', $query);
        $this->assertStringContainsString('Amount=1000', $query);
    }
}
