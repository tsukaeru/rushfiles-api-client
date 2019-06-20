<?php

namespace Tsukaeru\RushFiles;

use Tightenco\Collect\Support\Collection;
use Tightenco\Collect\Support\Arr;

class VirtualFile
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

    public function getChildren() : Collection
    {
        if ($this->isDirectory() && $this->children === null)
        {
            $rawData = $this->client->GetDirectoryChildren($this->getShareId(), $this->getInternalName(), $this->domain, $this->token);

            $self = $this;
            $this->children = collect($rawData)->mapWithKeys(function ($data) use ($self) {
                $file = new VirtualFile($data, $self->domain, $self->token, $self->client);
                return [$file->getInternalName() => $file];
            });
        }

        return $this->children;
    }

    public function getFiles() : Collection
    {
        return $this->getChildren()->filter(function ($item) {
            return $item->isFile();
        });
    }

    public function getDirectories() : Collection
    {
        return $this->getChildren()->filter(function ($item) {
            return $item->isDirectory();
        });
    }

    public function isDirectory() : bool
    {
        return !$this->isFile();
    }

    public function isFile() : bool
    {
        return Arr::get($this->properties, 'IsFile', false);
    }

    public function getInternalName() : string
    {
        return $this->properties['InternalName'] ?? $this->properties['Id'];
    }

    public function getUploadName() : ?string
    {
        return $this->properties['UploadName'] ?? null;
    }

    public function getName() : string
    {
        return $this->properties['PublicName'] ?? $this->properties['Name'];
    }

    public function getShareId() : string
    {
        return $this->properties['ShareId'] ?? $this->properties['Id'];
    }

    public function getTick() : int
    {
        return $this->properties['Tick'] ?? $this->properties['ShareTick'];
    }

    public function getShareTick() : int
    {
        return $this->properties['ShareTick'];
    }

    public function getSize() : int
    {
        return $this->properties['EndOfFile'] ?? 0;
    }

    public function getContent(bool $refresh = false)
    {
        if ($this->isDirectory())
        {
            return $this->getChildren($refresh);
        }

        if ($this->content === null || $refresh)
        {
            if ($this->getSize() > 0) {
                $this->content = $this->client->GetFileContent($this->getShareId(), $this->getUploadName(), $this->domain, $this->token);
            } else {
                $this->content = '';
            }
        }

        return $this->content;
    }

    public function save(string $path)
    {
        $path = trim($path);

        if ($path[-1] === DIRECTORY_SEPARATOR) $path .= $this->getName();

        if (!is_dir(dirname($path)))
        {
            mkdir(dirname($path), 0777, true);
        }

        if (!$this->isFile())
        {
            return $this->saveDir($path);
        }

        $bytes = file_put_contents($path, $this->getContent());

        if ($bytes === false)
        {
            throw new \Exception("Error saving file {$this->properties['PublicName']} to $path"); // @codeCoverageIgnore
        }

        return $bytes;
    }

    private function saveDir(string $path)
    {
        $path .= DIRECTORY_SEPARATOR;

        foreach ($this->getChildren() as $file) {
            $file->save($path);
        }

        return 0;
    }
}