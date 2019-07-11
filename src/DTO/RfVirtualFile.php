<?php

namespace Tsukaeru\RushFiles\DTO;

use Ramsey\Uuid\Uuid;

class RfVirtualFile extends BaseDTO
{
    const READ_ONLY           = 1;
    const HIDDEN              = 2;
    const SYSTEM              = 4;
    const DIRECTORY           = 16;
    const ARCHIVE             = 32;
    const DEVICE              = 64;
    const NORMAL              = 128;
    const TEMPORARY           = 256;
    const SPARSE_FILE         = 512;
    const REPARSE_POINT       = 1024;
    const COMPRESSED          = 2048;
    const OFFLINE             = 4096;
    const NOT_CONTENT_INDEXED = 8192;
    const ENCRYPTED           = 16384;
    const INTEGRITY_STREAM    = 32768;
    const NO_SCRUB_DATA       = 131072;

    /**
     * @param string $shareId
     * @param string $parentId
     * @param string|array $file Either path to a file/directory or array containing properties of Virtual File
     */
    public function __construct(string $shareId, string $parentId, $file)
    {
        if (is_string($file) && file_exists($file)) {
            $file = [
                'EndOfFile' => is_file($file) ? filesize($file) : 0,
                'PublicName' => basename($file),
                'Attributes' => is_dir($file) ? self::FILE_ATTRIBUTES['DIRECTORY'] : self::FILE_ATTRIBUTES['NORMAL'],
                'CreationTime' => date('c', filectime($file)),
                'LastAccessTime' => date('c', fileatime($file)),
                'LastWriteTime' => date('c', filemtime($file)),
            ];
        }

        if (!is_array($file)) {
            throw new \InvalidParameter("file parameter is neither an array nor path to en existing file.");
        }

        $this->properties = array_merge([
            'ShareId' => $shareId,
            'ParentId' => $parentId,
            'InternalName' => Uuid::uuid1(),
        ], $file);
    }

    public function getShareId()
    {
        return $this->properties['ShareId'];
    }

    public function getParentId()
    {
        return $this->properties['ParentId'];
    }

    public function getInternalName()
    {
        return $this->properties['InternalName'];
    }
}