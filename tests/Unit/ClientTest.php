<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tsukaeru\RushFiles\API\Client;
use function GuzzleHttp\json_encode;
use function GuzzleHttp\json_decode;
use Tsukaeru\RushFiles\User;
use GuzzleHttp\Client as GuzzleClient;
use org\bovigo\vfs\vfsStream;
use Tsukaeru\RushFiles\API\DTO\ClientJournal;
use Tsukaeru\RushFiles\API\DTO\RfVirtualFile;
use Tsukaeru\RushFiles\VirtualFile;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class ClientTest extends TestCase
{
    use ArraySubsetAsserts;

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
        $this->assertStringStartsWith('https://clientgateway.cloudfile.jp/api/users/admin@example.com/shares', (string)$request->getUri());
        $this->assertEquals(['Bearer token'], $request->getHeader('Authorization'));
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
        $this->assertEquals(['Bearer token'], $request->getHeader('Authorization'));
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

    public function testGetFile()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(200, [], json_encode(['Data' => [
            'ShareId' => 'shareId',
            'InternalName' => 'InternalName',
            'IsFile' => true
        ]])));

        $file = $client->GetFile('shareId', 'internalName', 'cloudfile.jp', 'token');

        $this->assertInstanceOf(VirtualFile::class, $file);

        $request = $history[0]['request'];
        $this->assertEquals('https://clientgateway.cloudfile.jp/api/shares/shareId/virtualfiles/internalName', $request->getUri());
    }

    public function testGetFileThrowsOnError()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(400));

        $this->expectExceptionMessage('Could not retrieve data about virtual file.');
        $this->expectExceptionCode(400);

        $client->GetFile('shareId', 'internalName', 'cloudfile.jp', 'token');
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

    public function testGetSetDeviceName()
    {
        $client = new Client;

        $this->assertEquals('tsukaeru/rushfiles-api-client@v0.1.0', $client->getDeviceName());

        $client->setDeviceName('TestName');

        $this->assertEquals('TestName', $client->getDeviceName());
        $this->assertEquals('4b101b46-823d-5e39-a00a-1fc81c2ab356', $client->getDeviceId());
    }

    public function testCreateVirtualFile()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $mock->append(new Response(200, [], json_encode([
            'Data' => [
                'Url' => 'https://filecache01.rushfiles.com/upload_url',
            ],
        ])));
        $mock->append(new Response(201));
        $mock->append(new Response(200, [], json_encode([
            'Data' => [
                'IsFile' => true,
            ],
        ])));

        $file_system = vfsStream::setup();
        $path = $file_system->url() . DIRECTORY_SEPARATOR . 'test.txt';
        file_put_contents($path, 'contents');

        $rfFile = new RfVirtualFile('share-id', 'parent-id', $path);
        $file = $client->CreateVirtualFile($rfFile, $path, 'rushfiles.com', 'token');

        $request = $history[0]['request'];
        $request->getBody()->rewind();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('https://filecache01.rushfiles.com/api/shares/share-id/files', (string)$request->getUri());
        $this->assertArraySubset([
            'RfVirtualFile' => [
                'EndOfFile' => 8,
                'PublicName' => 'test.txt',
                'Attributes' => RfVirtualFile::NORMAL,
                'CreationTime' => date('c', filectime($path)),
                'LastAccessTime' => date('c', fileatime($path)),
                'LastWriteTime' => date('c', filemtime($path)),
                'ShareId' => 'share-id',
                'ParrentId' => 'parent-id',
            ],
            'ClientJournalEventType' => ClientJournal::CREATE,
            'DeviceId' => 'd51b8f6c-3f0e-5f6e-9c93-58763a47185d',
        ], json_decode($request->getBody()->getContents(), true));

        $request = $history[1]['request'];
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('https://filecache01.rushfiles.com/upload_url', (string)$request->getUri());
        $this->assertEquals(['bytes 0-7/8'], $request->getHeader('Content-Range'));
        $this->assertEquals($request->getBody(), 'contents');
    }

    public function testGetAuthorizationCodeUrl()
    {
        $client = new Client();

        $client->setAuthority("https://auth.example.com");
        $client->setClientId('ClientId');
        $client->setRedirectUrl('https://example.org');

        $url = $client->GetAuthorizationCodeUrl();

        $this->assertEquals('https', parse_url($url, PHP_URL_SCHEME));
        $this->assertEquals('auth.example.com', parse_url($url, PHP_URL_HOST));
        $this->assertEquals('/connect/authorize', parse_url($url, PHP_URL_PATH));
        $query = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertArraySubset([
            'client_id' => 'ClientId',
            'redirect_uri' => 'https://example.org',
            'response_type' => 'code',
            'scope' => 'openid profile domain_api offline_access',
        ], $query);
    }

    public function testGetTokenThroughAuthorizationCode()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $accessToken = 'header.' . base64_encode(json_encode([
            'exp' => date_create("+5 min")->getTimestamp(),
            'sub' => 'username',
            'primary_domain' => 'example.com',
        ])) . '.signature';

        $mock->append(new Response(200, [], json_encode([
            'access_token' => $accessToken,
            'expires_in' => 300,
            'token_type' => 'Bearer',
            'refresh_token' => 'refresh',
        ])));

        $client->setClientId('client_id');
        $client->setClientSecret('client_secret');
        $client->setRedirectUrl('https://example.com');

        $token = $client->GetTokenThroughAuthorizationCode('AuthCode');

        $request = $history[0]['request'];
        $this->assertEquals('https://auth.rushfiles.com/connect/token', $request->getUri());
        $this->assertEquals('Basic ' . base64_encode('client_id:client_secret'), $request->getHeader('Authorization')[0]);
        $body = [];
        $request->getBody()->rewind();
        parse_str($request->getBody()->getContents(), $body);
        $this->assertArraySubset([
            'grant_type' => 'authorization_code',
            'code' => 'AuthCode',
            'redirect_uri' => 'https://example.com',
        ], $body);

        $this->assertEquals($accessToken, $token->getAccessToken());
        $this->assertTrue($token->isValid());
        $this->assertTrue($token->isRefreshable());
    }

    public function testGetTokenThroughResourceOwnerPasswordCredentials()
    {
        $history = [];
        list($client, $mock) = $this->prepareClient($history);

        $accessToken = 'header.' . base64_encode(json_encode([
            'exp' => date_create("+5 min")->getTimestamp(),
            'sub' => 'username',
            'primary_domain' => 'example.com',
        ])) . '.signature';

        $mock->append(new Response(200, [], json_encode([
            'access_token' => $accessToken,
            'expires_in' => 300,
            'token_type' => 'Bearer',
            'refresh_token' => 'refresh',
        ])));

        $client->setClientId('client_id');
        $client->setClientSecret('client_secret');

        $token = $client->GetTokenThroughResourceOwnerPasswordCredentials('UserName', 'Password');

        $request = $history[0]['request'];
        $this->assertEquals('https://auth.rushfiles.com/connect/token', $request->getUri());
        $this->assertEquals('Basic ' . base64_encode('client_id:client_secret'), $request->getHeader('Authorization')[0]);
        $body = [];
        $request->getBody()->rewind();
        parse_str($request->getBody()->getContents(), $body);
        $this->assertArraySubset([
            'grant_type' => 'password',
            'username' => 'UserName',
            'password' => 'Password',
            'scope' => 'openid profile domain_api offline_access',
        ], $body);

        $this->assertEquals($accessToken, $token->getAccessToken());
        $this->assertTrue($token->isValid());
        $this->assertTrue($token->isRefreshable());
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