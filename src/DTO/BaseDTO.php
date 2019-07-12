<?php

namespace Tsukaeru\RushFiles\DTO;

use function GuzzleHttp\json_encode;

class BaseDTO
{
    /**
     * @var array
     */
    protected $properties;

    /**
     * @return array
     */
    public function getData()
    {
        return $this->properties;
    }

    /**
     * @return string
     */
    public function getJSON()
    {
        return json_encode($this->properties);
    }
}