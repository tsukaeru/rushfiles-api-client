<?php

namespace Tsukaeru\RushFiles;

use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Stream;
use Tsukaeru\RushFiles\VirtualFile\File;
use Tsukaeru\RushFiles\VirtualFile\Directory;
use Tsukaeru\RushFiles\VirtualFile\Share;

abstract class VirtualFile
{
    /**
     * @var Collection|null of VirtualFile
     */
    protected $children = null;

    /**
     * @var array raw properties from API
     */
    protected $properties;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var Tsukaeru\RushFiles\Client
     */
    protected $client;

    public function __construct(array $rawData, string $domain, string $token, Client $client)
    {
        $this->properties = $rawData;
        $this->domain = $domain;
        $this->token = $token;
        $this->client = $client;
    }

    public static function create($rawData, $domain, $token, Client $client, VirtualFile $parent = null)
    {
        if (Arr::get($rawData, 'IsFile') === true) {
            return new File($rawData, $domain, $token, $client, $parent);
        }

        if (Arr::get($rawData, 'IsFile') === false) {
            return new Directory($rawData, $domain, $token, $client, $parent);
        }

        if (Arr::has($rawData, 'ShareType')) {
            return new Share($rawData, $domain, $token, $client);
        }

        throw new \InvalidArgumentException("Could not detect VirtualFile resource type from passed properties.");
    }

    public function isDirectory()
    {
        return !$this->isFile();
    }

    abstract public function isFile();

    public function getInternalName()
    {
        return $this->properties['InternalName'];
    }

    public function getName()
    {
        return $this->properties['PublicName'];
    }

    public function getShareId()
    {
        return $this->properties['ShareId'];
    }

    public function getTick()
    {
        return $this->properties['Tick'];
    }

    abstract public function getSize();

    abstract public function getContent($refresh = false);

    abstract public function save(string $path) : int;

    protected function buildPath($path)
    {
        $path = trim($path);

        if ($path[-1] === DIRECTORY_SEPARATOR) $path .= $this->getName();

        if (!is_dir(dirname($path)))
        {
            mkdir(dirname($path), 0777, true);
        }

        return $path;
    }
}