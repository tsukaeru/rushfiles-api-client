<?php

namespace Tsukaeru\RushFiles\API;

class AuthToken {
    /**
     * @var string auth access token
     */
    private $accessToken;

    /**
     * @var string|null auth refresh token
     */
    private $refreshToken = null;

    /**
     * @var \DateTime access token valid until
     */
    private $validUntil;

    /**
     * @var array
     */
    private $domains;

    /**
     * @var string
     */
    private $username;

    public function __construct($tokenData)
    {
        $this->accessToken = $tokenData['access_token'];
        $this->refreshToken = $tokenData['refresh_token'];

        $this->decodeAccessToken();
    }

    private function decodeAccessToken()
    {
        $payload = base64_decode(explode('.', $this->accessToken)[1]);
        $payload = json_decode($payload, true);

        // TODO: add checks

        $this->validUntil = new \DateTime("@{$payload['exp']}");
        $this->domains = array_merge([$payload['primary_domain']], (array)$payload['domains']);
        $this->username = $payload['sub'];
    }

    public function isValid()
    {
        return (new \DateTime("now")) < $this->validUntil;
    }

    public function isRefreshable()
    {
        return !is_null($this->refreshToken);
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getDomains()
    {
        return $this->domains;
    }
}