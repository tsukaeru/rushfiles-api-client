<?php

namespace Tsukaeru\RushFiles\API\DTO;

use function GuzzleHttp\json_encode;
use Illuminate\Support\Collection;

class BaseDTO
{
    /**
     * @var Collection
     */
    protected $properties;

    /**
     * @return array
     */
    public function getData()
    {
        return $this->properties->all();
    }

    /**
     * @return string
     */
    public function getJSON()
    {
        return json_encode($this->getData());
    }
}