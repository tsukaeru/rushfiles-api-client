<?php

namespace Tsukaeru\RushFiles;

use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Stream;
use Tsukaeru\RushFiles\VirtualFile\File;
use Tsukaeru\RushFiles\VirtualFile\Directory;
use Tsukaeru\RushFiles\VirtualFile\Share;
use Tsukaeru\RushFiles\DTO\CreatePublicLink;

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
    protected $path = './';

    /**
     * @var string
     */
    protected $content;

    /**
     * @var Tightenco\Collect\Support\Collection
     */
    protected $links;

    /**
     * @var Tsukaeru\RushFiles\VirtualFile
     */
    protected $parent;

    /**
     * @var Client
     */
    protected $client;

    public function __construct($rawData, $domain, $token, Client $client, VirtualFile $parent = null)
    {
        $this->properties = collect($rawData);
        $this->domain = $domain;
        $this->token = $token;
        $this->client = $client;
        $this->parent = $parent;
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

    abstract public function download();

    public function getParent()
    {
        if ($this->parent === null && $this->getShareId() !== $this->getInternalName()) {
            $this->parent = $this->client->GetFile($this->getShareId(), $this->getInternalName(), $this->domain, $this->token);
            // TODO: Do I need a special path for shares?
        }

        return $this->parent;
    }

    public function delete()
    {
        $this->client->DeleteVirtualFile($this->getShareId(), $this->getInternalName(), $this->domain, $this->token);

        if ($this->parent && $this->parent->children) {
            $this->parent->children->forget($this->getName());
        }
    }

    public function getPublicLinks($refresh = false)
    {
        if ($this->links !== null || $refresh)
        {
            $linksRaw = $this->client->GetPublicLinks($this->getShareId(), $this->getInternalName(), $this->domain, $this->token);
            $this->links = collect($linksRaw)->map(function ($data) {
                return new PublicLink($data);
            });
        }

        return $this->links;
    }

    public function createPublicLink($config = [], $fetch = PublicLink::OBJECT)
    {
        if (is_string($config)) {
            $fetch = $config;
            $config = [];
        }

        $dto = new CreatePublicLink($this->getShareId(), $this->getInternalName(), $config);
        $link = $this->client->CreatePublicLink($dto, $this->domain, $this->token);

        if ($fetch === PublicLink::OBJECT) {
            $id = [];
            preg_match("/id=([[:alnum:]]*)/", $link, $id);
            $id = $id[1];

            $links = $this->getPublicLinks(true);
            foreach ($links as $link) {
                if ($link->getId() === $id) {
                    return $link;
                }
            }

            throw new \Exception("New public link could not be found.");
        }

        return $link;
    }

    public function setPath($path)
    {
        $this->path = trim($path);

        if ($this->path[-1] === DIRECTORY_SEPARATOR) $this->path .= $this->getName();

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }
}