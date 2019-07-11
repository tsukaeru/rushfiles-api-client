<?php

namespace Tsukaeru\RushFiles\VirtualFile;

use Tsukaeru\RushFiles\VirtualFile;
use Illuminate\Support\Collection;

class Directory extends VirtualFile
{
    public function getChildren($refresh = false)
    {
        if ($this->children === null || $refresh)
        {
            $rawData = $this->client->GetDirectoryChildren($this->getShareId(), $this->getInternalName(), $this->domain, $this->token);

            $self = $this;
            $this->children = collect($rawData)->mapWithKeys(function ($data) use ($self) {
                $file = VirtualFile::create($data, $self->domain, $self->token, $self->client);
                return [$file->getInternalName() => $file];
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

    public function save()
    {
        $path = $this->buildPath($path) . DIRECTORY_SEPARATOR;
        $bytes = 0;

        foreach ($this->getChildren() as $file) {
            $bytes += $file->save($path);
        }

        return $bytes;
    }
}