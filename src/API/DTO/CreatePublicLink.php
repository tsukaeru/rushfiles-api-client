<?php

namespace RushFiles\API\DTO;

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

        $this->properties->put('EnablePassword', !empty($password));

        return $this;
    }

    /**
     * Set validity period or removes the restriction if parameter does not cast to a positive integer
     *
     * @param int $days
     *
     * @return self
     */
    public function setDaysToExpire($days)
    {
        if ((int)$days > 0) {
            $this->properties->put('DaysToExpire', $days);
        } else {
            $this->properties->forget('DaysToExpire');
        }

        return $this;
    }

    /**
     * Returns validity period in days or null if unlimited
     *
     * @return int
     */
    public function getDaysToExpire()
    {
        return $this->properties->get('DaysToExpire');
    }

    /**
     * Set max uses limit or removes the restriction if parameter does not cast to a positive integer
     *
     * @param int $count
     *
     * @return self
     */
    public function setMaxUse($count)
    {
        if ((int)$count > 0) {
            $this->properties->set('MaxUse', $count);
        } else {
            $this->properties->forget('MasUse');
        }

        return $this;
    }

    /**
     * Returns maximum number of usage or null if unlimited
     *
     * @return int|null
     */
    public function getMaxUse()
    {
        return $this->properties->get('MaxUse');
    }

    /**
     * @param string $msg
     *
     * @return self
     */
    public function setMessage($msg)
    {
        $this->properties->put('Message', (string)$msg);

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->properties->get('Message');
    }
}