<?php

namespace Tsukaeru\RushFiles;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class User
{
    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var Collection
     */
    protected $domainTokens = [];

    /**
     * @var Collection|null of Tsukaeru\RushFiles\VirtualFile
     */
    protected $shares = null;

    /**
     * @var Client
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
            $this->domainTokens = Collection::make($tokens);
        } else {
            $this->domainTokens = Collection::make($tokens)->mapWithKeys(function ($data) {
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
            $self = $this;
            $this->shares = $this->domainTokens->flatMap(function ($token, $domain) use ($self) {
                $rawShares = $this->client->GetUserShares($this->username, $token, $domain);
                return collect($rawShares)->mapWithKeys(function ($data) use ($domain, $token, $self) {
                    return [$data['Id'] => VirtualFile::create($data, $domain, $token, $self->client)];
                });
            });
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