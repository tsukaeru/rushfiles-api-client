<?php

namespace Tsukaeru\RushFiles;

use Tsukaeru\RushFiles\API\AuthToken;
use Tsukaeru\RushFiles\API\Client;

class User
{
    /**
     * @var Collection|null of Tsukaeru\RushFiles\API\VirtualFile
     */
    protected $shares = null;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var AuthToken
     */
    protected $authToken;

    /**
     * @param AuthToken $auth
     * @param Client $client API Client used for connection
     */
    public function __construct(AuthToken $auth, Client $client)
    {
        $this->authToken = $auth;
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->authToken->getUsername();
    }

    /**
     * @return array
     */
    public function getDomains()
    {
        return $this->authToken->getDomains();
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->authToken->getAccessToken();
    }

    /**
     * @return VirtualFile\Share[]
     */
    public function getShares()
    {
        if ($this->shares === null)
        {
            $self = $this;
            $this->shares = collect($this->getDomains())->flatMap(function ($domain) use ($self) {
                $rawShares = $this->client->GetUserShares($this->getUsername(), $self->getAccessToken(), $domain);
                return collect($rawShares)->mapWithKeys(function ($data) use ($domain, $self) {
                    return [$data['Id'] => VirtualFile::create($data, $domain, $self->getAccessToken(), $self->client)];
                });
            });
        }

        return $this->shares->all();
    }

    /**
     * @return Share
     */
    public function getShare($id)
    {
        return collect($this->getShares())->get($id);
    }
}