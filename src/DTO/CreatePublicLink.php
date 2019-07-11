<?php

namespace Tsukaeru\RushFiles\DTO;

class CreatePublicLink extends BaseDTO
{
    public function __construct($ShareId, $InternalName, $properties = [])
    {
        $this->properties = collect(array_merge($properties, [
            'ShareId' => $ShareId,
            'InternalName' => $InternalName,
        ]));

        $this->setPassword(collect($properties)->get('Password'));
    }

    public function setPassword($password)
    {
        $this->properties->put('Password', $password);

        if (empty($password)) {
            $this->properties->put('EnablePassword', false);
        }
    }
}