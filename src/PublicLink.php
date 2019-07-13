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
}