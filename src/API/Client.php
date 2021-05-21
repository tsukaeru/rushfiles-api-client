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
use Tsukaeru\RushFiles\API\DTO\EventReport;
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

    const DEVICE_PC = 0;
    const DEVICE_ANDROID_TABLET = 1;
    const DEVICE_ANDROID_PHONE = 2;
    const DEVICE_IPHONE = 3;
    const DEVICE_IPAD = 4;
    const DEVICE_IPAD_MINE = 5;
    const DEVICE_WINDOWS_PHONE = 6;
    const DEVICE_WINDOWS_TABLET = 7;
    const DEVICE_UNKNOWN = 8;
    const DEVICE_WEB_CLIENT = 9;
    const DEVICE_MAC = 10;
    const DEVICE_IPAD_MINI = 11;

    /**
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $issuer = 'https://auth.rushfiles.com';

    /**
     * @var string
     */
    protected $deviceName = 'tsukaeru/rushfiles-api-client@v0.1.0';

    /**
     * @var string
     */
    private $deviceOS;

    /**
     * @var int
     */
    private $deviceType = self::DEVICE_UNKNOWN;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    protected $redirectUrl;

    /**
     * @var array
     */
    protected $scopes = ['openid', 'profile', 'domain_api', 'offline_access'];

    /**
     * @var array
     */
    private $defaultHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    public function __construct()
    {
        $this->deviceOS = php_uname('s') . ' ' . php_uname('');

        $this->client = new HttpClient([
            'verify' => false,
        ]);
    }

    /**
     * @param string $deviceName
     *
     * @return self
     */
    public function setDeviceName($deviceName)
    {
        $this->deviceName = $deviceName;

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
        return Uuid::uuid5(self::CLIENT_NAMESPACE_UUID, $this->deviceName);
    }

    /**
     * @param int $deviceType
     * 
     * @return self
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    /**
     * @return int
     */
    public function getDeviceType()
    {
        return $this->deviceType;
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
     * @param string $clientId
     * 
     * @return self
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientSecret
     * 
     * @return self
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * @return string
     */
    private function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $redirectUrl
     * 
     * @return self
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    private function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param string $issuer URL of authority (without the path)
     * 
     * @return self
     */
    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;

        return $this;
    }

    /**
     * return string
     */
    public function getIssuer()
    {
        return $this->issuer;
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
     * 
     */
    public function GetTokenThroughResourceOwnerPasswordCredentials($username, $password)
    {
        return $this->GetAccessToken([
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'scope' => implode(' ', $this->scopes),
        ]);
    }

    public function GetAuthorizationCodeUrl()
    {
        return $this->issuer . '/connect/authorize?' . http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUrl(),
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'arc_values' => "deviceName:{$this->getDeviceName()} deviceOs:{$this->deviceOS} deviceType:{$this->getDeviceType()}",
        ], "", "&", PHP_QUERY_RFC3986);
    }

    public function GetTokenThroughAuthorizationCode($code)
    {
        return $this->GetAccessToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->getRedirectURL(),
        ]);
    }

    private function GetAccessToken($requestData)
    {
        try {
            $response = $this->client->post($this->TokenURL(), [
                'auth' => [$this->getClientId(), $this->getClientSecret()],
                'form_params' => $requestData,
            ]);
            $tokenData = json_decode($response->getBody(), true);
            return new AuthToken($tokenData);
        } catch (ClientException $exception) {
            
        }
    }

    /**
     * @param string $username
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
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
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
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
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
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
     * @param string $shareId
     * @param string $uploadName
     * @param string $domain
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
     *
     * @return StreamInterface|string
     */
    public function GetFileContent($shareId, $uploadName, $domain, $token)
    {
        try {
            $request = new Request('GET', $this->FileURL($domain, $shareId, $uploadName), $this->AuthHeaders($token));
            $response = $this->client->send($request, ['decode_content' => false]);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not download file.");
        }

        return $response->getBody();
    }

    /**
     * @param RfVirtualFile $rfFile
     * @param string $path Path to file/directory to be uploaded/created
     * @param string $domain
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
     *
     * @return VirtualFile
     */
    public function CreateVirtualFile(RfVirtualFile $rfFile, $path, $domain, $token)
    {
        $journal = new ClientJournal($rfFile, ClientJournal::CREATE, $this->getDeviceId());

        $headers = array_merge($this->defaultHeaders, $this->AuthHeaders($token));

        try {
            $request = new Request('POST', $this->FilesURL($domain, $rfFile->getShareId()), $headers, $journal->getJSON());
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not create a new virtual file.");
        }

        $data = json_decode($response->getBody(), true);

        if (isset($data['Data']['Url']) && $data['Data']['Url']) {
            $this->uploadFileContents($data['Data']['Url'], $token, $path);
        }

        return $this->GetFile($rfFile->getShareId(), $rfFile->getInternalName(), $domain, $token);
    }

    /**
     * @param string $shareId
     * @param string $parentId
     * @param string $internalName
     * @param string $path Path to file/directory to be updated
     * @param string $domain
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
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
            'Attributes' => is_dir($path) ? RfVirtualFile::DIRECTORY : RfVirtualFile::NORMAL,
            'CreationTime' => date('c', filectime($path)),
            'LastAccessTime' => date('c', fileatime($path)),
            'LastWriteTime' => date('c', filemtime($path)),
            'Tick' => 1,
        ];

        $journal = [
            'RfVirtualFile' => $fileProperties,
            'TransmitId' => Uuid::uuid1(),
            'ClientJournalEventType' => ClientJournal::UPDATE,
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
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
     */
    public function DeleteVirtualFile($shareId, $internalName, $domain, $token)
    {
        $journal = [
            'TransmitId' => Uuid::uuid1(),
            'ClientJournalEventType' => ClientJournal::DELETE,
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
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
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
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
     *
     * @return array|string
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
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
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
     * @param EventReport $eventReport
     * @param string $shareId
     * @param string $virtualFileId
     * @param string $domain
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
     *
     * @return array
     */
    public function GetFileEventReport(EventReport $eventReport, $shareId, $virtualFileId, $domain, $token)
    {
        $headers = array_merge($this->defaultHeaders, $this->AuthHeaders($token));

        try {
            $request = new Request('POST', $this->FileEventReportURL($domain, $shareId, $virtualFileId), $headers, $eventReport->getJSON());
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $this->throwException($exception->getResponse(), "Could not get an event report.");
        }

        $body = json_decode($response->getBody(), true);

        return $body['Data'];
    }

    /**
     * @param string $url
     * @param Tsukaeru\RushFiles\API\AuthToken|string $token
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
            if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
                $this->throwException($response, "Error while uploading file content.");
            }
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
     * @param Tsukaeru\RushFiles\API\AuthToken|string
     */
    private function AuthHeaders($token)
    {
        return [
            'Authorization' => 'Bearer ' . ($token instanceof AuthToken ? $token->getAccessToken() : $token),
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

    private function FileEventReportURL($domain, $shareId, $virtualFileId)
    {
        return "https://clientgateway.$domain/api/shares/$shareId/virtualfiles/$virtualFileId/eventreport";
    }

    private function TokenURL()
    {
        return $this->issuer . '/connect/token';
    }
}
