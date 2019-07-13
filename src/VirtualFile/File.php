<?php

namespace RushFiles\VirtualFile;

use RushFiles\VirtualFile;

class File extends VirtualFile
{
    /**
     * @inheritDoc
     */
    public function isFile()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getUploadName()
    {
        return $this->properties->get('UploadName');
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return $this->properties->get('EndOfFile');
    }

    /**
     * @inheritDoc
     */
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

    /**
     * Downloads file and saves it to set up path.
     *
     * @inheritDoc
     */
    public function download()
    {
        $this->createDirectory();

        $bytes = file_put_contents($this->path, $this->getContent());

        if ($bytes === false)
        {
            throw new \Exception("Error saving file {$this->properties->get('PublicName')} to $path"); // @codeCoverageIgnore
        }

        return $bytes;
    }

    public function upload($path)
    {
        $newFile = $this->client->UpdateVirtualFile($this->getShareId(), $this->getParent()->getInternalName(), $this->getInternalName(), $path, $this->domain, $this->token);
        $this->properties = $newFile->properties;
    }

    /**
     * Create directory for a file.
     */
    protected function createDirectory()
    {
        if (!is_dir(dirname($this->path)))
        {
            mkdir(dirname($this->path), 0777, true);
        }
    }
}