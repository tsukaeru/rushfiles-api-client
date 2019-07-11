<?php

namespace Tsukaeru\RushFiles;

use Tightenco\Collect\Support\Arr;

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

    public function __construct(string $username, array $tokens, Client $client)
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

    public function getUsername() : string
    {
        return $this->username;
    }

    public function getDomains() : iterable
    {
        return $this->domainTokens->keys();
    }

    public function getToken(string $domain = null) : string
    {
        return $domain ? $this->domainTokens->get($domain) : $this->domainTokens->first();
    }

    public function getShares() : iterable
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

    public function getShare(string $id)
    {
        return $this->getShares()->get($id);
    }
}