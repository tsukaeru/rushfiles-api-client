<?php

namespace Tsukaeru\RushFiles\VirtualFile;

use Tsukaeru\RushFiles\VirtualFile;

class File extends VirtualFile
{
    public function isFile()
    {
        return true;
    }

    public function getUploadName()
    {
        return $this->properties->get('UploadName');
    }

    public function getSize()
    {
        return $this->properties['EndOfFile'];
    }

    public function getContent($refresh = false)
    {
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

    public function download()
    {
        $this->createDirectory();

        $bytes = file_put_contents($this->path, $this->getContent());

        if ($bytes === false)
        {
            throw new \Exception("Error saving file {$this->properties['PublicName']} to $path"); // @codeCoverageIgnore
        }

        return $bytes;
    }

    public function upload($path)
    {
        $this->properties = $this->client->UpdateVirtualFile($this->getShareId(), $this->getParent()->getInternalName(), $this->getInternalName(), $path, $this->domain, $this->token);
    }

    protected function createDirectory()
    {
        if (!is_dir(dirname($this->path)))
        {
            mkdir(dirname($this->path), 0777, true);
        }
    }
}