<?php

namespace Tsukaeru\RushFiles\VirtualFile;

use Tsukaeru\RushFiles\VirtualFile;
use Illuminate\Support\Collection;

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
    public function download($path)
    {
        $path = $this->preparePath($path);

        $bytes = file_put_contents($path, $this->getContent());

        if ($bytes === false)
        {
            throw new \Exception("Error saving file {$this->getName()} to {$this->path}"); // @codeCoverageIgnore
        }

        return $bytes;
    }

    // untested
    public function upload($path)
    {
        $newFile = $this->client->UpdateVirtualFile($this->getShareId(), $this->getParent()->getInternalName(), $this->getInternalName(), $path, $this->domain, $this->token);
        $this->properties = Collection::make($newFile->properties);
    }

    /**
     * Process path for download
     */
    protected function preparePath($path)
    {
        $path = trim($path);

        if (substr($path, -1) === DIRECTORY_SEPARATOR) $path .= $this->getName();

        if (!is_dir(dirname($path)))
        {
            mkdir(dirname($path), 0777, true);
        }

        return $path;
    }
}