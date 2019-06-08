<?php

namespace Tsukaeru\RushFiles;

use Psr\Http\Client\ClientInterface;
use GuzzleHttp\Psr7\Request;
use function GuzzleHttp\json_decode;

class Client
{
    /**
     * @var Psr\Http\Client\ClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $deviceName = 'tsukaeru.net/rushfiles';

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function Login(string $username, string $password, string $domain)
    {
        $headers = [
            'useremail' => $username,
            'password2' => base64_encode($password),
            'devicename' => 'unknown',
        ];

        $request = new Request('GET', $this->GetLoginURL($domain), $headers);

        $response = $this->client->sendRequest($request);

        $data = json_decode($response->getBody());

        $token = $data->PrimaryUserDomain->UserDomainToken ?? null;

        if ($token === null)
            throw new \Exception("Login failed with error code: {$data->ErrorCode}");

        $request = new Request('GET', $this->GetGatewayLoginURL($domain, $username, $token));

        $response = $this->client->sendRequest($request);

        $data = json_decode($response->getBody());

        $filecaches = $data->FilecacheUrls ?? null;

        if ($filecaches === null)
            throw new \Exception("Could not retrieve filecache URLs.");

        return new DomainToken($username, $domain, $filecaches, $token);
    }

    private function GetLoginURL(string $domain)
    {
        return "https://clientgateway.$domain/Login2.aspx";
    }

    private function GetGatewayLoginURL(string $domain, string $username, string $token)
    {
        return "https://clientgateway.$domain/ClientLogin.aspx?userEmail=$username&token=$token";
    }
}