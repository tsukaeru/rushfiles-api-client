<?php

namespace Tsukaeru\RushFiles;

use Illuminate\Support\Arr;

class User
{
    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var array
     */
    protected $domainTokens = [];

    /**
     * @var Collection|null of Tsukaeru\RushFiles\VirtualFile
     */
    protected $shares = null;

    /**
     * @var Tsukaeru\RushFiles\Client
     */
    protected $client;

    /**
     * @param string $username
     * @param array $tokens domain=>domainToken array
     * @param Client $client API Client used for connection
     */
    public function __construct($username, $tokens, Client $client)
    {
        $this->username = $username;

        if (Arr::isAssoc($tokens)) {
            $this->domainTokens = collect($tokens);
        } else {
            $this->domainTokens = collect($tokens)->mapWithKeys(function ($data) {
                return [$data['DomainUrl'] => $data['DomainToken']];
            });
        }

        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return array
     */
    public function getDomains()
    {
        return $this->domainTokens->keys();
    }

    /**
     * Returns domainToken for the domain or first available token if domain is not specified.
     * (user can often have shares on only one domain and then there is no need to specify it)
     *
     * @return string
     */
    public function getToken($domain = null)
    {
        return $domain ? $this->domainTokens->get($domain) : $this->domainTokens->first();
    }

    /**
     * @return array
     */
    public function getShares()
    {
        if ($this->shares === null)
        {
            $this->shares = collect();
            foreach ($this->domainTokens as $domain => $token) {
                $rawShares = $this->client->GetUserShares($this->username, $token, $domain);
                $self = $this;
                $objShares = collect($rawShares)->mapWithKeys(function ($data) use ($domain, $token, $self) {
                    return [$data['Id'] => VirtualFile::create($data, $domain, $token, $self->client)];
                });
                $this->shares = $this->shares->merge($objShares);
            }
        }

        return $this->shares;
    }

    /**
     * @return Share
     */
    public function getShare($id)
    {
        return $this->getShares()->get($id);
    }
}