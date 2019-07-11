<?php

namespace Tsukaeru\RushFiles\DTO;

use function GuzzleHttp\json_encode;

class BaseDTO
{
    /**
     * @var array
     */
    protected $properties;

    public function getData()
    {
        return $this->properties;
    }

    public function getJSON()
    {
        return json_encode($this->properties);
    }
}