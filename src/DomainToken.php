<?php

namespace Tsukaeru\RushFiles;

class DomainToken
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string[]
     */
    protected $filecaches;

    /**
     * @var string
     */
    protected $token;

    public function __construct(string $username, string $domain, array $filecaches, string $token)
    {
        $this->username = $username;
        $this->domain = $domain;
        $this->filecaches = $filecaches;
        $this->token = $token;
    }
}