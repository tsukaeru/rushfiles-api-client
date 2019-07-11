<?php

namespace Tsukaeru\RushFiles;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\json_decode;
use Ramsey\Uuid\Uuid;
use function GuzzleHttp\json_encode;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;

class Client
{
    /**
     * Namespace used for generating device ids from device name.
     */
    const CLIENT_NAMESPACE_UUID = '8737930c-8f13-11e9-910b-7e7a262c9c6d';

    /**
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $deviceName = 'Tsukaeru\RushFiles';

    /**
     * @var string
     */
    private $deviceOS;

    /**
     * Generated from CLIENT_NAMESPACE_UUID and default device name
     * @var string
     */
    private $deviceId = 'dae9022f-96c2-52fa-8aa1-d758a22759cc';

    private $defaultHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    public function __construct()
    {
        $this->deviceOS = php_uname('s') . ' ' . php_uname('');
        $this->deviceId = Uuid::uuid5(self::CLIENT_NAMESPACE_UUID, $this->deviceName);

        $this->client = new HttpClient();
    }

    public function setDeviceName($deviceName)
    {
        $this->deviceName = $deviceName;
        $this->deviceId = Uuid::uuid5(self::CLIENT_NAMESPACE_UUID, $this->deviceName);

        return $this;
    }

    public function getDeviceName()
    {
        return $this->deviceName;
    }

    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function setHttpClient(HttpClient $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Automates getting user's domain, registering device and retrieving tokens,
     * and packs everything into User object.
     */
    public function Login(string $username, string $password, string $domain = null) : User
    {
        if (!is_string($domain)) {
            $domain = $this->GetUserDomain($username);
        }

        $this->RegisterDevice($username, $password, $domain);

        $tokens = $this->GetDomainTokens($username, $password, $domain);

        return new User($username, $tokens, $this);
    }

    public function GetUserDomain(string $username) : string
    {
        $request = new Request('GET', $this->UserDomainURL($username));
        $response = $this->client->send($request);
        list(,$domain) = explode(',', $response->getBody());

        return $domain;
    }

    /**
     * RegisterDevice must be called at least once for every username/deviceId combination.
     * Subsequent calls won't cause errors so it can be called every time, or invocation
     * can be remembered not to make needless calls.
     * This client does not keep track of registration on itself.
     */
    public function RegisterDevice(string $username, string $password, string $domain) : void
    {
        $deviceAssociation = [
            'UserName' => $username,
            'Password' => $password,
            'DeviceName' => $this->deviceName,
            'DeviceOs' => $this->deviceOS,
            'DeviceType' => 8, // unknown
        ];

        try {
            $request = new Request('PUT', $this->RegisterDeviceURL($domain, $this->getDeviceId()), $this->defaultHeaders, json_encode($deviceAssociation));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not register device.");
        }

        json_decode($response->getBody());
        }

    public function GetUserShares(string $username, string $token, string $domain) : iterable
    {
        try {
        $request = new Request('GET', $this->UsersShareURL($domain, $username), $this->AuthHeaders($token));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not retrieve user's shares.");
        }

        $data = json_decode($response->getBody(), true);

        return $data['Data'];
    }

    public function GetDirectoryChildren(string $shareId, string $internalName, string $domain, $token)
    {
        try {
        $request = new Request('GET', $this->DirectoryChildrenURL($domain, $shareId, $internalName), $this->AuthHeaders($token));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not retrieve directory children.");
        }

        $data = json_decode($response->getBody(), true);

        return $data['Data'];
    }

    public function GetDomainTokens(string $username, string $password, string $domain) : array
    {
        $loginData = [
            'UserName' => $username,
            'Password' => $password,
            'DeviceId' => $this->getDeviceId(),
            'Longitude' => 0,
            'Latitude' => 0,
        ];

        try {
        $request = new Request('POST', $this->DomainTokensURL($domain), $this->defaultHeaders, json_encode($loginData));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Fail to retrieve domain's token.");
        }

        $body = json_decode($response->getBody(), true);

        return $body['Data']['DomainTokens'];
    }

    /**
     * @return StreamInterface|string
     */
    public function GetFileContent(string $shareId, string $uploadName, string $domain, string $token)
    {
        try {
            $response = $this->client->get($this->FileURL($domain, $shareId, $uploadName), $this->AuthHeaders($token));
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not download file.");
        }

        return $response->getBody();
    }

    /**
     * @param string|Tsukaeru\RushFiles\User
     */
    private function AuthHeaders($token)
    {
        return [
            'Authorization' => 'DomainToken ' . (is_object($token) ? $token->getToken() : $token),
        ];
    }

    private function UserDomainURL(string $username) : string
    {
        return "https://global.rushfiles.com/getuserdomain.aspx?useremail=$username";
    }

    private function DomainTokensURL(string $domain) : string
    {
        return "https://clientgateway.$domain/api/domaintokens";
    }

    private function RegisterDeviceURL(string $domain, string $deviceId) : string
    {
        return "https://clientgateway.$domain/api/devices/$deviceId";
    }

    private function UsersShareURL(string $domain, string $username, bool $includeAssociations = false)
    {
        return "https://clientgateway.$domain/api/users/$username/shares" . ($includeAssociations ? '?includeAssociation=true' : '');
    }

    private function DirectoryChildrenURL(string $domain, string $shareId, string $internalName)
    {
        return "https://clientgateway.$domain/api/shares/$shareId/virtualfiles/$internalName/children";
    }

    private function FileURL(string $domain, string $shareId, string $uploadName)
    {
        return "https://filecache01.$domain/api/shares/$shareId/files/$uploadName";
    }
}