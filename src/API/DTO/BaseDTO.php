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
        return $this->properties->map(function ($item, $key) {
            if ($item instanceof BaseDTO) {
                return $item->getData();
            } else {
                return $item;
            }
        });
    }

    /**
     * @return string
     */
    public function getJSON()
    {
        return json_encode($this->getData());
    }
}