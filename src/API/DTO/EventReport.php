<?php

namespace Tsukaeru\RushFiles\API\DTO;

use Illuminate\Support\Collection;

class EventReport extends BaseDTO
{
    /**
     * @param array $EventTypes
     * @param string $UserId
     * @param DateTime $From
     * @param DateTime $To
     */
    public function __construct($EventTypes, $UserId, $From, $To)
    {
        $this->properties = Collection::make([
            'EventTypes' => $EventTypes,
            'UserId' => $UserId,
            'From' => $From->format(DATE_RFC3339_EXTENDED),
            'To' => $To->format(DATE_RFC3339_EXTENDED),
        ]);
    }

    /**
     * @return array
     */
    public function getEventTypes()
    {
        return $this->properties->get('EventTypes');
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->properties->get('UserId');
    }

    /**
     * @return DateTime
     */
    public function getFrom()
    {
        return DateTime::createFromFormat(DATE_RFC3339_EXTENDED, $this->properties->get('From'));
    }

    /**
     * @return DateTime
     */
    public function getTo()
    {
        return DateTime::createFromFormat(DATE_RFC3339_EXTENDED, $this->properties->get('To'));
    }
}
