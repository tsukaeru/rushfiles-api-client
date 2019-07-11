<?php

namespace Tsukaeru\RushFiles;

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
        $this->properties = collect($rawData);
    }

    public function getId()
    {
        return $this->properties->get('Id');
    }
}