<?php

namespace Tsukaeru\RushFiles\VirtualFile;

use Tsukaeru\RushFiles\VirtualFile;
use Tightenco\Collect\Support\Collection;

class Directory extends VirtualFile
{
    public function getChildren(bool $refresh = false) : iterable
    {
        if ($this->isDirectory() && ($this->children === null || $refresh))
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

    public function isFile() : bool
    {
        return false;
    }

    public function getSize(): int
    {
        return count($this->getChildren());
    }

    public function getContent(bool $refresh = false)
    {
        return $this->getChildren($refresh);
    }

    public function save(string $path): int
    {
        $path = $this->buildPath($path) . DIRECTORY_SEPARATOR;
        $bytes = 0;

        foreach ($this->getChildren() as $file) {
            $bytes += $file->save($path);
        }

        return $bytes;
    }
}