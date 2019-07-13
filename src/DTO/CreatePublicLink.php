<?php

namespace Tsukaeru\RushFiles\DTO;

use Illuminate\Support\Collection;

class CreatePublicLink extends BaseDTO
{
    /**
     * @param string $ShareId
     * @param string $InternalName
     * @param array $properties
     */
    public function __construct($ShareId, $InternalName, $properties = [])
    {
        $this->properties = Collection::make(array_merge($properties, [
            'ShareId' => $ShareId,
            'InternalName' => $InternalName,
        ]));

        $this->setPassword($this->properties->get('Password'));
    }

    /**
     * @param string $password
     *
     * @return self
     */
    public function setPassword($password)
    {
        $this->properties->put('Password', $password);

        if (empty($password)) {
            $this->properties->put('EnablePassword', false);
        }

        return $this;
    }
    }
}