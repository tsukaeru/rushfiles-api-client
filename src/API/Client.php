<?php

namespace Tsukaeru\RushFiles\API;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\json_decode;
use Ramsey\Uuid\Uuid;
use function GuzzleHttp\json_encode;
use Tsukaeru\RushFiles\API\DTO\CreatePublicLink;
use Tsukaeru\RushFiles\API\DTO\ClientJournal;
use Tsukaeru\RushFiles\API\DTO\RfVirtualFile;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Psr\SimpleCache\CacheInterface;
use Tightenco\Collect\Support\Arr;
use Tsukaeru\RushFiles\User;
use Tsukaeru\RushFiles\VirtualFile;

class Client
{
    /**
     * Namespace used for generating device ids from device name.
     */
    const CLIENT_NAMESPACE_UUID = '8737930c-8f13-11e9-910b-7e7a262c9c6d';

    const CODE = [
        'DENIED_ACCESS' => 0,
        'SUCCESS' => 1,
        'CONFLICT' => 2,
        'DUPLICATE' => 3,
        'NO_REQUEST' => 4,
        'NOT_YET_IMPLEMENTED' => 5,
        'INSUFFICIENT_DATA' => 6,
        'ALREAY_EXISTS' => 7,
        'FAILED_TALKING_TO_SERVER' => 8,
        'IGNORE_DELETE' => 9,
        'ENTITY_NOT_FOUND' => 10,
        'STORAGE_LIMIT_EXCEEDED' => 11,
        'ILLEGAL_CHARACTERS' => 12,
        'PARENT_NOT_FOUND' => 13,
        'JOURNAL_ALREADY_EXISTS' => 14,
        'FILE_IS_LOCKED' => 15,
        'BAD_RANGE' => 16,
        'MISSING_CREATE_EVENT' =>17,
    ];

    /**
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $deviceName = 'tsukaeru/rushfiles-api-client@v0.1.0';

    /**
     * @var string
     */
    private $deviceOS;

    /**
     * Generated from CLIENT_NAMESPACE_UUID and default device name
     * @var string
     */
    private $deviceId = 'dae9022f-96c2-52fa-8aa1-d758a22759cc';

    /**
     * @var array
     */
    private $defaultHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    /**
     * @var Psr\SimpleCache\CacheInterface
     */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->deviceOS = php_uname('s') . ' ' . php_uname('');
        $this->deviceId = Uuid::uuid5(self::CLIENT_NAMESPACE_UUID, $this->deviceName);

        $this->client = new HttpClient();

        $this->cache = $cache;
    }

    /**
     * @param string $deviceName
     *
     * @return self
     */
    public function setDeviceName($deviceName)
    {
        $this->deviceName = $deviceName;
        $this->deviceId = Uuid::uuid5(self::CLIENT_NAMESPACE_UUID, $this->deviceName);

        return $this;
    }

    /**
     * @return string
     */
    public function getDeviceName()
    {
        return $this->deviceName;
    }

    /**
     * @return string
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * @param \GuzzleHttp\Client $client
     */
    public function setHttpClient(HttpClient $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Automates getting user's domain, registering device and retrieving tokens,
     * and packs everything into User object.
     *
     * @param string $username
     * @param string $password
     * @param string $domain
     *
     * @return User
     */
    public function Login($username, $password, $domain = null)
    {
        $username = strtolower($username);

        if (!is_string($domain)) {
            $domain = $this->GetUserDomain($username);
        }

        $this->RegisterDevice($username, $password, $domain);

        $tokens = $this->GetDomainTokens($username, $password, $domain);

        return new User($username, $tokens, $this);
    }

    /**
     * @param string $username
     *
     * @return string
     */
    public function GetUserDomain($username)
    {
        try {
            $request = new Request('GET', $this->UserDomainURL($username));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not retrieve user's domain.");
        }

        list($retUsername,$domain) = explode(',', $response->getBody());

        if ($retUsername !== strtolower($username) || empty($domain)) {
            throw new \Exception("Could not retrieve user's domain.");
        }

        return $domain;
    }

    /**
     * RegisterDevice must be called at least once for every username/deviceId combination.
     * Subsequent calls won't cause errors so it can be called every time, or invocation
     * can be remembered not to make needless calls.
     * This client does not keep track of registration on itself.
     *
     * @param string $username
     * @param string $password
     * @param string $domain
     */
    public function RegisterDevice($username, $password, $domain)
    {
        $deviceAssociation = [
            'UserName' => $username,
            'Password' => $password,
            'DeviceName' => $this->deviceName,
            'DeviceOs' => $this->deviceOS,
            'DeviceType' => 8, // unknown
        ];

        $cacheKey = str_replace('-', '_', self::CLIENT_NAMESPACE_UUID) . "." . str_replace('@', '_', $username) . ".deviceId";

        if ($this->cache->get($cacheKey) == $this->getDeviceId()) {
            return;
        }

        try {
            $request = new Request('PUT', $this->RegisterDeviceURL($domain, $this->getDeviceId()), $this->defaultHeaders, json_encode($deviceAssociation));
            $this->client->send($request);

            $this->cache->set($cacheKey, $this->getDeviceId());
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not register device.");
        }
    }

    /**
     * @param string $username
     * @param string $token
     * @param string $domain
     *
     * @return array
     */
    public function GetUserShares($username, $token, $domain)
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

    /**
     * @param string $shareId
     * @param string $internalName
     * @param string $domain
     * @param string $token
     *
     * @return array
     */
    public function GetDirectoryChildren($shareId, $internalName, $domain, $token)
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

    /**
     * @param string $shareId
     * @param string $internalName
     * @param string $domain
     * @param string $token
     *
     * @return VirtualFile
     */
    public function GetFile($shareId, $internalName, $domain, $token)
    {
        try {
            $request = new Request('GET', $this->VirtualFileURL($domain, $shareId, $internalName), $this->AuthHeaders($token));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not retrieve data about virtual file.");
        }

        $data = json_decode($response->getBody(), true);

        return VirtualFile::create($data['Data'], $domain, $token, $this);
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $domain
     *
     * @return array
     */
    public function GetDomainTokens($username, $password, $domain)
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
     * @param string $shareId
     * @param string $uploadName
     * @param string $domain
     * @param string $token
     *
     * @return StreamInterface|string
     */
    public function GetFileContent($shareId, $uploadName, $domain, $token)
    {
        try {
            $response = $this->client->get($this->FileURL($domain, $shareId, $uploadName), $this->AuthHeaders($token));
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not download file.");
        }

        return $response->getBody();
    }

    /**
     * @param RfVirtualFile $rfFile
     * @param string $path Path to file/directory to be uploaded/created
     * @param string $domain
     * @param string $token
     *
     * @return VirtualFile
     */
    public function CreateVirtualFile(RfVirtualFile $rfFile, $path, $domain, $token)
    {
        $journal = new ClientJournal($rfFile, ClientJournal::CREATE, $this->getDeviceId());

        $headers = array_merge($this->defaultHeaders, $this->AuthHeaders($token));

        try {
            $request = new Request('POST', $this->FilesURL($domain, $rfFile->getShareId()), $headers, json_encode($journal));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not create a new virtual file.");
        }

        $data = json_decode($response->getBody(), true);

        $uploadURL = $data['Data']['Url'];

        if ($uploadURL) {
            $this->uploadFileContents($uploadURL, $token, $path);
        }

        return $this->GetFile($rfFile->getShareId(), $rfFile->getInternalName(), $domain, $token);
    }

    /**
     * @param string $shareId
     * @param string $parentId
     * @param string $internalName
     * @param string $path Path to file/directory to be updated
     * @param string $domain
     * @param string $token
     *
     * @return VirtualFile
     */
    public function UpdateVirtualFile($shareId, $parentId, $internalName, $path, $domain, $token)
    {
        $fileProperties = [
            'InternalName' => $internalName,
            'ShareId' => $shareId,
            'ParrentId' => $parentId,
            'EndOfFile' => is_file($path) ? filesize($path) : 0,
            'PublicName' => basename($path),
            'Attributes' => is_dir($path) ? self::FILE_ATTRIBUTES['DIRECTORY'] : self::FILE_ATTRIBUTES['NORMAL'],
            'CreationTime' => date('c', filectime($path)),
            'LastAccessTime' => date('c', fileatime($path)),
            'LastWriteTime' => date('c', filemtime($path)),
            'Tick' => 1,
        ];

        $journal = [
            'RfVirtualFile' => $fileProperties,
            'TransmitId' => Uuid::uuid1(),
            'ClientJournalEventType' => self::CLIENT_JOURNAL_EVENT_TYPE['UPDATE'],
            'DeviceId' => $this->getDeviceId(),
        ];

        $headers = array_merge($this->defaultHeaders, $this->AuthHeaders($token));

        try {
            $request = new Request('PUT', $this->FileURL($domain, $shareId, $internalName), $headers, json_encode($journal));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not create a new virtual file.");
        }

        $data = json_decode($response->getBody(), true);

        $uploadURL = $data['Data']['Url'];

        if ($uploadURL) {
            $this->uploadFileContents($uploadURL, $token, $path);
        }

        return $this->GetFile($shareId, $fileProperties['InternalName'], $domain, $token);
    }

    /**
     * @param string $shareId
     * @param string $internalName
     * @param string $domain
     * @param string $token
     */
    public function DeleteVirtualFile($shareId, $internalName, $domain, $token)
    {
        $journal = [
            'TransmitId' => Uuid::uuid1(),
            'ClientJournalEventType' => self::CLIENT_JOURNAL_EVENT_TYPE['DELETE'],
            'DeviceId' => $this->getDeviceId(),
        ];

        $headers = array_merge($this->defaultHeaders, $this->AuthHeaders($token));

        try {
            $request = new Request('DELETE', $this->FileURL($domain, $shareId, $internalName), $headers, json_encode($journal));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not delete file.");
        }
    }

    /**
     * @param string $shareId
     * @param string $virtualFileId
     * @param string $domain
     * @param string $token
     *
     * @return array
     */
    public function GetPublicLinks($shareId, $virtualFileId, $domain, $token)
    {
        try {
            $request = new Request('GET', $this->FilePublicLinksURL($domain, $shareId, $virtualFileId), $this->AuthHeaders($token));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not get public links.");
        }

        $body = json_decode($response->getBody(), true);

        return $body['Data'];
    }

    /**
     * @param CreatePublicLink $linkDto
     * @param string $domain
     * @param string $token
     *
     * @return array
     */
    public function CreatePublicLink(CreatePublicLink $linkDto, $domain, $token)
    {
        $headers = array_merge($this->defaultHeaders, $this->AuthHeaders($token));

        try {
            $request = new Request('POST', $this->PublicLinksURL($domain), $headers, $linkDto->getJSON());
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not create public link.");
        }

        $body = json_decode($response->getBody(), true);

        return $body['Data']['FullLink'];
    }

    /**
     * @param string $id
     * @param string $domain
     * @param string $token
     *
     * @return array
     */
    public function GetPublicLink($id, $domain, $token)
    {
        try {
            $request = new Request('GET', $this->PublicLinksURL($domain, $id), $this->AuthHeaders($token));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not retrieve details on a public link.");
        }

        $body = json_decode($response->getBody());

        return $body['Data'];
    }

    /**
     * @param string $url
     * @param string $token
     * @param string $path
     */
    private function uploadFileContents($url, $token, $path)
    {
        $size = filesize($path);
        $headers = array_merge([
            'Content-Range' => 'bytes 0-' . ($size - 1) . '/' . $size,
        ], $this->AuthHeaders($token));

        try {
            $request = new Request('PUT', $url, $headers, fopen($path, 'r'));
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Count not upload file's contents.");
        }
    }

    /**
     * @param Response $response
     * @param string $msg
     *
     * @throws \Exception
     */
    private function throwException(Response $response, $msg = "Request error.")
    {
        $msg .= " HTTP Status Code: {$response->getStatusCode()}";

        if ($response->getBody()) {
            $data = \json_decode($response->getBody(), true);

            if ($data !== false) {
                if (isset($data['Message'])) $msg .= "\nMessage: " . $data['Message'];
                if (isset($data['ResponseInfo'])) $msg .= "\nResponse: {$data['ResponseInfo']['ResponseCode']} - {$data['ResponseInfo']['ResponseCode']}";
            }
        }

        throw new \Exception($msg, $response->getStatusCode());
    }

    /**
     * @param string|Tsukaeru\RushFiles\API\User
     */
    private function AuthHeaders($token)
    {
        return [
            'Authorization' => 'DomainToken ' . (is_object($token) ? $token->getToken() : $token),
        ];
    }

    private function UserDomainURL($username)
    {
        return "https://global.rushfiles.com/getuserdomain.aspx?useremail=$username";
    }

    private function DomainTokensURL($domain)
    {
        return "https://clientgateway.$domain/api/domaintokens";
    }

    private function RegisterDeviceURL($domain, $deviceId)
    {
        return "https://clientgateway.$domain/api/devices/$deviceId";
    }

    private function UsersShareURL($domain, $username, $includeAssociations = false)
    {
        return "https://clientgateway.$domain/api/users/$username/shares" . ($includeAssociations ? '?includeAssociation=true' : '');
    }

    private function DirectoryChildrenURL($domain, $shareId, $internalName)
    {
        return "https://clientgateway.$domain/api/shares/$shareId/virtualfiles/$internalName/children";
    }

    private function VirtualFileURL($domain, $shareId, $internalName)
    {
        return "https://clientgateway.$domain/api/shares/$shareId/virtualfiles/$internalName";
    }

    private function FileURL($domain, $shareId, $uploadName)
    {
        return "https://filecache01.$domain/api/shares/$shareId/files/$uploadName";
    }

    private function FilesURL($domain, $shareId)
    {
        return "https://filecache01.$domain/api/shares/$shareId/files";
    }

    private function FilePublicLinksURL($domain, $shareId, $virtualFileId)
    {
        return "https://clientgateway.$domain/api/shares/$shareId" . (!empty($virtualFileId) ? "/virtualfiles/$virtualFileId" : '') . "/publiclinks";
    }

    private function PublicLinksURL($domain, $linkId = null)
    {
        return "https://clientgateway.$domain/api/publiclinks" . (!empty($linkId) ? "/$linkId" : '');
    }
}