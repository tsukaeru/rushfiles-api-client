<?php

namespace Tsukaeru\RushFiles\VirtualFile;

use Tsukaeru\RushFiles\VirtualFile;
use Illuminate\Support\Collection;
use Tsukaeru\RushFiles\DTO\RfVirtualFile;

class Directory extends VirtualFile
{
    public function getChildren($refresh = false)
    {
        if ($this->children === null || $refresh)
        {
            $rawData = $this->client->GetDirectoryChildren($this->getShareId(), $this->getInternalName(), $this->domain, $this->token);

            $self = $this;
            $this->children = collect($rawData)->mapWithKeys(function ($data) use ($self) {
                $file = VirtualFile::create($data, $self->domain, $self->token, $self->client, $self);
                return [$file->getName() => $file];
            });
        }

        return $this->children;
    }

    public function getFiles()
    {
        return $this->getChildren()->filter(function ($item) {
            return $item->isFile();
        });
    }

    public function getDirectories()
    {
        return $this->getChildren()->filter(function ($item) {
            return $item->isDirectory();
        });
    }

    public function isFile()
    {
        return false;
    }

    public function getSize()
    {
        return $this->getChildren()->count();
    }

    public function getContent($refresh = false)
    {
        return $this->getChildren($refresh);
    }

    public function download()
    {
        $this->createDirectory();

        $dir = $this->path . DIRECTORY_SEPARATOR;
        $bytes = 0;

        foreach ($this->getChildren() as $file) {
            $bytes += $file->setPath($dir)->download();
        }

        return $bytes;
    }

    public function uploadFile($path)
    {
        $rfFile = new RfVirtualFile($this->getShareId(), $this->getParent()->getInternalName(), $path);
        $properties = $this->client->CreateVirtualFile($rfFile, $path, $this->domain, $this->token);
        $newFile = VirtualFile::create($properties, $this->domain, $this->token, $this->client, $this);
        $this->children->put(basename($path), $newFile);
        return $newFile;
    }

    protected function createDirectory()
    {
        if (!is_dir($this->path))
        {
            mkdir($this->path, 0777, true);
        }
    }
}