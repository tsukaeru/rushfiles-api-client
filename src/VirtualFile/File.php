<?php

namespace Tsukaeru\RushFiles\VirtualFile;

use Tsukaeru\RushFiles\VirtualFile;

class File extends VirtualFile
{
    public function isFile() : bool
    {
        return true;
    }

    public function getUploadName() : ?string
    {
        return $this->properties['UploadName'] ?? null;
    }

    public function getSize(): int
    {
        return $this->properties['EndOfFile'];
    }

    public function getContent(bool $refresh = false)
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

    public function save(string $path) : int
    {
        $path = $this->buildPath($path);

        $bytes = file_put_contents($path, $this->getContent());

        if ($bytes === false)
        {
            throw new \Exception("Error saving file {$this->properties['PublicName']} to $path"); // @codeCoverageIgnore
        }

        return $bytes;
    }
}