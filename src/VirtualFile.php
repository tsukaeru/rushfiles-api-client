<?php

namespace Tsukaeru\RushFiles;

use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Tsukaeru\RushFiles\VirtualFile\File;
use Tsukaeru\RushFiles\VirtualFile\Directory;
use Tsukaeru\RushFiles\VirtualFile\Share;
use Tsukaeru\RushFiles\API\DTO\CreatePublicLink;
use Tsukaeru\RushFiles\API\DTO\EventReport;
use Tsukaeru\RushFiles\API\Client;

abstract class VirtualFile
{
    /**
     * @var Collection|null of VirtualFile
     */
    protected $children = null;

    /**
     * @var Collection raw properties from API
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
     * @var Collection
     */
    protected $links;

    /**
     * @var Directory
     */
    protected $parent;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param array $rawData properties returned by RushFiles API
     * @param string $domain domain from where the virtual file was taken
     * @param string $token  domain token
     * @param Client $client RushFiles' API Client object
     * @param VirtualFile|null $parent parent of the file
     */
    public function __construct($rawData, $domain, $token, Client $client, VirtualFile $parent = null)
    {
        $this->properties = Collection::make($rawData);
        $this->domain = $domain;
        $this->token = $token;
        $this->client = $client;
        $this->parent = $parent;
    }

    /**
     * @see VirtualFile::__construct
     */
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

    /**
     * @return bool
     */
    public function isDirectory()
    {
        return !$this->isFile();
    }

    /**
     * @return bool
     */
    abstract public function isFile();

    /**
     * @return string
     */
    public function getInternalName()
    {
        return $this->properties->get('InternalName');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->properties->get('PublicName');
    }

    /**
     * @return string
     */
    public function getShareId()
    {
        return $this->properties->get('ShareId');
    }

    /**
     * @return int
     */
    public function getTick()
    {
        return $this->properties->get('Tick');
    }

    /**
     * @return int
     */
    abstract public function getSize();

    /**
     * @param bool $refresh Force reload content
     * @return string|array
     */
    abstract public function getContent($refresh = false);

    /**
     * @return int Number of bytes written
     *
     * @throws \Exception
     */
    abstract public function download($path);

    /**
     * @return Directory|null
     */
    public function getParent()
    {
        if ($this->parent === null && $this->getShareId() !== $this->getInternalName()) {
            $this->parent = $this->client->GetFile($this->getShareId(), $this->getInternalName(), $this->domain, $this->token);
            // TODO: Do I need a special path for shares?
        }

        return $this->parent;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    public function delete()
    {
        $this->client->DeleteVirtualFile($this->getShareId(), $this->getInternalName(), $this->domain, $this->token);

        if ($this->parent && $this->parent->children) {
            $this->parent->children->forget($this->getInternalName());
        }
    }

    /**
     * @param bool $refresh
     * @return PublicLink[]
     */
    public function getPublicLinks($refresh = false)
    {
        if ($this->links === null || $refresh)
        {
            $linksRaw = $this->client->GetPublicLinks($this->getShareId(), $this->getInternalName(), $this->domain, $this->token);
            $self = $this;
            $this->links = Collection::make($linksRaw)->map(function ($data) use ($self) {
                return new PublicLink($data, $self->domain);
            });
        }

        return $this->links->all();
    }

    /**
     * @param array|string $config @see https://clientgateway.rushfiles.com/swagger/ui/index#!/PublicLink/PublicLink_CreatePublicLink
     * @param string $fetch either PublicLink::OBJECT or PublicLink::STRING
     *
     * @throws \Exception
     */
    public function createPublicLink($config = [], $fetch = PublicLink::OBJECT)
    {
        if (is_string($config)) {
            $fetch = $config;
            $config = [];
        }

        $dto = new CreatePublicLink($this->getShareId(), $this->getInternalName(), $config);
        $link = $this->client->CreatePublicLink($dto, $this->domain, $this->token);

        if ($fetch === PublicLink::STRING) {
            return $link;
        }

        // Caller requested object
        $id = [];
        preg_match("/id=([[:alnum:]]*)/", $link, $id);
        $id = $id[1];

        $link = Collection::make($this->getPublicLinks(true))->first(function($link) use ($id) {
            return $id === $link->getId();
        });

        if ($link === null)
            throw new \Exception("New public link could not be found.");

        return $link;
    }

    /**
     * @param EventReport $eventReport
     *
     * @return array
     */
    public function getEvents(EventReport $eventReport)
    {
        return $this->client->GetFileEventReport($eventReport, $this->getShareId(), $this->getInternalName(), $this->domain, $this->token);
    }

    public function getFullPath()
    {
        if ($this->getParent() && $this->getParent()->getInternalName() !== $this->getInternalName()) {
            return $this->getParent()->getFullPath() . DIRECTORY_SEPARATOR . $this->getName();
        } else {
            return DIRECTORY_SEPARATOR . $this->getName();
        }
    }
}
