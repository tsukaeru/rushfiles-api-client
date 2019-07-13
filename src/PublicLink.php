<?php

namespace Tsukaeru\RushFiles;

use Illuminate\Support\Collection;

class PublicLink
{
    public const STRING = 'string';
    public const OBJECT = 'object';

    /**
     * @var array
     */
    protected $properties;

    public function __construct($rawData)
    {
        $this->properties = Collection::make($rawData);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->properties->get('Id');
    }

    /**
     * @return string
     */
    public function getShareId()
    {
        return $this->properties->get('ShareId');
    }

    /**
     * @return string
     */
    public function getVirtualFileId()
    {
        return $this->properties->get('VirtualFileId');
    }

    /**
     * @return bool
     */
    public function getIsFile()
    {
        return $this->properties->get('IsFile');
    }

    /**
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->properties->get('CreatedBy');
    }

    /**
     * @return bool
     */
    public function isPasswordEnabled()
    {
        return $this->properties->get('IsPasswordProtected');
    }
}