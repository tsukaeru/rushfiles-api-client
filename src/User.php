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
}