<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tsukaeru\RushFiles\Client;
use function GuzzleHttp\json_encode;
use function GuzzleHttp\json_decode;
use Tsukaeru\RushFiles\User;
use GuzzleHttp\Client as GuzzleClient;
use Tsukaeru\RushFiles\VirtualFile;

class ClientTest extends TestCase
{
    public function testGetUserDomain()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(200, [], 'admin@example.com,cloudfile.jp'));

        $domain = $client->GetUserDomain('admin@example.com');

        $this->assertEquals('cloudfile.jp', $domain);

        $request = $history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('https://global.rushfiles.com/getuserdomain.aspx?useremail=admin@example.com', $request->getUri());
    }

    public function testRegisterDevice()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(201, [], json_encode(['Message' => 'Ok.'])));

        $client->RegisterDevice('admin@example.com', 'password', 'cloudfile.jp');

        $request = $history[0]['request'];
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertRegExp('/https:\/\/clientgateway.cloudfile.jp\/api\/devices\/[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}/', $request->getURI());
        $this->assertArraySubset([
            'Accept' => ['application/json'],
            'Content-Type' => ['application/json'],
        ], $request->getHeaders());
        $body = json_decode($request->getBody(), true);
        $this->assertArraySubset([
            'UserName' => 'admin@example.com',
            'Password' => 'password',
        ], $body);
        $this->assertArraySubset(['DeviceName', 'DeviceOs', 'DeviceType'], collect($body)->keys()->sort()->values());
    }

    public function testRegisterDeviceThrowsOnError()
    {
        list($client, $mock) = $this->prepareClient();

        $mock->append(new Response(400, [], json_encode([
            'Message' => 'ErrorMsg',
        ])));

        $this->expectExceptionMessage('ErrorMsg');

        $client->RegisterDevice('admin@example.com', 'password', 'cloudfile.jp');
    }

    public function testGetDomainTokens()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(200, [], json_encode([
            'Message' => 'Ok.',
            'Data' => [
                'DomainTokens' => [
                    'cloudfile.jp' => 'token',
                ],
            ],
        ])));

        $tokens = $client->GetDomainTokens('admin@example.com', 'password', 'cloudfile.jp');

        $this->assertEquals(['cloudfile.jp' => 'token'], $tokens);

        $request = $history[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('https://clientgateway.cloudfile.jp/api/domaintokens', $request->getUri());
        $this->assertArraySubset([
            'Accept' => ['application/json'],
            'Content-Type' => ['application/json'],
        ], $request->getHeaders());
        $body = json_decode($request->getBody(), true);
        $this->assertArraySubset([
            'UserName' => 'admin@example.com',
            'Password' => 'password',
        ], $body);
        $this->assertArraySubset(['DeviceId', 'Latitude', 'Longitude'], collect($body)->keys()->sort()->values());
    }

    public function testGetDomainTokensThrowsOnError()
    {
        list($client, $mock) = $this->prepareClient();

        $mock->append(new Response(400, [], json_encode([
            'Message' => 'ErrorMsg',
        ])));

        $this->expectExceptionMessage('ErrorMsg');

        $client->GetDomainTokens('admin@example.com', 'password', 'cloudfile.jp');
    }

    public function testLoginWithoutDomain()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(200, [], 'admin@example.com,cloudfile.jp'));
        $mock->append(new Response(201, [], json_encode(['Message' => 'Ok.'])));
        $mock->append(new Response(200, [], json_encode([
            'Message' => 'Ok.',
            'Data' => [
                'DomainTokens' => [
                    'cloudfile.jp' => 'token',
                ],
            ],
        ])));

        $login = $client->Login('admin@example.com', 'password');

        $this->assertInstanceOf(User::class, $login);
        $this->assertEquals('admin@example.com', $login->getUsername());
        $this->assertEquals(['cloudfile.jp'], $login->getDomains()->toArray());
    }

    public function testLoginWithDomain()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(201, [], json_encode(['Message' => 'Ok.'])));
        $mock->append(new Response(200, [], json_encode([
            'Message' => 'Ok.',
            'Data' => [
                'DomainTokens' => [
                    'cloudfile.jp' => 'token',
                ],
            ],
        ])));

        $login = $client->Login('admin@example.com', 'password', 'cloudfile.jp');

        $this->assertInstanceOf(User::class, $login);
        $this->assertEquals('admin@example.com', $login->getUsername());
        $this->assertEquals(['cloudfile.jp'], $login->getDomains()->toArray());
    }

    public function testGetUserShares()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(200, [], json_encode([
            'Message' => 'Ok.',
            'Data' => ['shares'],
        ])));

        $shares = $client->GetUserShares('admin@example.com', 'token', 'cloudfile.jp');

        $this->assertEquals(['shares'], $shares);

        $request = $history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertStringStartsWith('https://clientgateway.cloudfile.jp/api/users/admin@example.com/shares', $request->getUri());
        $this->assertArraySubset(['Authorization' => ['DomainToken token']], $request->getHeaders());
    }

    public function testGetUserSharesThrowsOnError()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(400, [], json_encode([
            'Message' => 'ErrorMsg',
        ])));

        $this->expectExceptionMessage('ErrorMsg');

        $client->GetUserShares('admin@example.com', 'token', 'cloudfile.jp');
    }

    public function testGetDirectoryChildren()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(200, [], json_encode([
            'Message' => 'Ok.',
            'Data' => ['children'],
        ])));

        $children = $client->GetDirectoryChildren('shareId', 'internalName', 'cloudfile.jp', 'token');

        $this->assertEquals(['children'], $children);

        $request = $history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('https://clientgateway.cloudfile.jp/api/shares/shareId/virtualfiles/internalName/children', $request->getUri());
        $this->assertArraySubset(['Authorization' => ['DomainToken token']], $request->getHeaders());
    }

    public function testGetDirectoryChildrenThrowsOnError()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(400, [], json_encode([
            'Message' => 'ErrorMsg',
        ])));

        $this->expectExceptionMessage('ErrorMsg');

        $client->GetDirectoryChildren('shareId', 'internalName', 'cloudfile.jp', 'token');
    }

    public function testGetFileContent()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(200, [], 'content'));

        $content = $client->GetFileContent('shareId', 'uploadName', 'cloudfile.jp', 'token');

        $this->assertEquals('content', $content);

        $request = $history[0]['request'];
        $this->assertEquals('https://filecache01.cloudfile.jp/api/shares/shareId/files/uploadName', $request->getUri());
    }

    public function testGetFileContentThrowsOnError()
    {
        list($client, $mock) = $this->prepareClient();
        $mock->append(new Response(400));

        $this->expectExceptionMessage('Could not download file.');
        $this->expectExceptionCode(400);

        $client->GetFileContent('shareId', 'uploadName', 'cloudfile.jp', 'token');
    }

    private function prepareClient(array &$history = [])
    {
        $mock = new MockHandler();

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $guzzle = new GuzzleClient(['handler' => $stack]);

        $client = new Client();
        $client->setHttpClient($guzzle);

        return [$client, $mock];
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    private function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}