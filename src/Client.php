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

    public function GetUserDomain(string $username) : string
    {
        $request = new Request('GET', $this->UserDomainURL($username));
        $response = $this->client->sendRequest($request);
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

        $request = new Request('PUT', $this->RegisterDeviceURL($domain, $this->deviceId), $this->defaultHeaders, json_encode($deviceAssociation));

        $response = $this->client->sendRequest($request);

        $data = json_decode($response->getBody());

        if ($data->Message !== "Ok.") {
            throw new \Exception("Could not register device: " . $data->Message);
        }
    }

    public function GetUserShares(string $username, string $token, string $domain) : iterable
    {
        $request = new Request('GET', $this->UsersShareURL($domain, $username), $this->AuthHeaders($token));

        $response = $this->client->sendRequest($request);

        $data = json_decode($response->getBody(), true);

        if ($data['Message'] !== "Ok.")
            throw new \Exception("Could not retrieve user's shares. Error message: " . $data['Message']);

        return $data['Data'];
    }

    public function GetDirectoryChildren(string $shareId, string $internalName, string $domain, $token)
    {
        $request = new Request('GET', $this->DirectoryChildrenURL($domain, $shareId, $internalName), $this->AuthHeaders($token));

        $response = $this->client->sendRequest($request);

        $data = json_decode($response->getBody(), true);

        if ($data['Message'] !== 'Ok.')
            throw new \Exception("Could not retrieve directory children. Error message: " . $data['Message']);

        return $data['Data'];
    }

    public function GetDomainTokens(string $username, string $password, string $domain) : array
    {
        $loginData = [
            'UserName' => $username,
            'Password' => $password,
            'DeviceId' => $this->deviceId,
            'Longitude' => 0,
            'Latitude' => 0,
        ];

        $request = new Request('POST', $this->DomainTokensURL($domain), $this->defaultHeaders, json_encode($loginData));

        $response = $this->client->sendRequest($request);

        $body = json_decode($response->getBody(), true);

        if ($body['Message'] !== "Ok.")
            throw new \Exception("Fail to retrieve domain's token: {$body['Message']}");

        return $body['Data']['DomainTokens'];
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
}