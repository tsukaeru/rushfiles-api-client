<?php

namespace Tsukaeru\RushFiles\VirtualFile;

use Tsukaeru\RushFiles\VirtualFile;
use Illuminate\Support\Collection;
use Tsukaeru\RushFiles\API\DTO\RfVirtualFile;

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
    public function download($path)
    {
        $path = $this->preparePath($path);

        $dir = $path . DIRECTORY_SEPARATOR;
        $bytes = 0;

        foreach ($this->getChildren() as $file) {
            $bytes += $file->download($dir);
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
        $newFile = $this->client->CreateVirtualFile($rfFile, $path, $this->domain, $this->token);
        if ($this->children !== null)
            $this->children->put(basename($path), $newFile);
        
        return $newFile;
    }

    /**
     * Process path for download
     */
    protected function preparePath($path)
    {
        $path = trim($path);

        if (substr($path, -1) === DIRECTORY_SEPARATOR) $path .= $this->getName();

        if (!is_dir($path))
        {
            mkdir($path, 0777, true);
        }

        return $path;
    }
}