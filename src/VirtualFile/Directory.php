<?php

namespace RushFiles\VirtualFile;

use RushFiles\VirtualFile;
use Illuminate\Support\Collection;
use RushFiles\API\DTO\RfVirtualFile;

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
            $this->children = Collection::make($rawData)->mapWithKeys(function ($data) use ($self) {
                $file = VirtualFile::create($data, $self->domain, $self->token, $self->client, $self);
                return [$file->getInternalName() => $file];
            });
        }

        return $this->children->all();
    }

    /**
     * @return File[]
     */
    public function getFiles()
    {
        return Collection::make($this->getChildren())->filter(function ($item) {
            return $item->isFile();
        })->all();
    }

    /**
     * @return Directory[]
     */
    public function getDirectories()
    {
        return Collection::make($this->getChildren())->filter(function ($item) {
            return $item->isDirectory();
        })->all();
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
        return count($this->getChildren());
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
     * @return VirtualFile
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