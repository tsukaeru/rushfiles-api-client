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

    /**
     * @var array
     */
    private $rawData;

    public function __construct($tokenData)
    {
        $this->rawData = $tokenData;

        $this->decodeTokenData();
    }

    private function decodeTokenData()
    {
        $this->accessToken = $this->rawData['access_token'];
        $this->refreshToken = $this->rawData['refresh_token'];

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

    public function __toString()
    {
        return (string)$this->accessToken;
    }

    public function toArray()
    {
        return $this->rawData;
    }
}