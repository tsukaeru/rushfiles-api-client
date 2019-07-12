<?php

namespace Tsukaeru\RushFiles\VirtualFile;

use Tsukaeru\RushFiles\VirtualFile;
use Illuminate\Support\Collection;
use Tsukaeru\RushFiles\DTO\RfVirtualFile;

class Directory extends VirtualFile
{
    /**
     * @param bool $refresh Force reload data about directory contents
     *
     * @return VirtualFile[]
     */
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

    /**
     * @return File[]
     */
    public function getFiles()
    {
        return $this->getChildren()->filter(function ($item) {
            return $item->isFile();
        });
    }

    /**
     * @return Directory[]
     */
    public function getDirectories()
    {
        return $this->getChildren()->filter(function ($item) {
            return $item->isDirectory();
        });
    }

    /**
     * @inheritDoc
     */
    public function isFile()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return $this->getChildren()->count();
    }

    /**
     * Get array of files and folders in directory.
     *
     * @inheritDoc
     */
    public function getContent($refresh = false)
    {
        return $this->getChildren($refresh);
    }

    /**
     * Recursively download folder and all its contents to set path.
     *
     * @inheritDoc
     */
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

    /**
     * Upload local file to RF directory
     *
     * @param string $path Path to local file
     *
     * @return File
     */
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