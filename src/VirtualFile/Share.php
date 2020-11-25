<?php

namespace Tsukaeru\RushFiles\VirtualFile;

class Share extends Directory
{
    const READ_ONLY = 0;
    const READ_WRITE = 1;
    const OWNER = 2;

    /**
     * @inheritDoc
     */
    public function getInternalName()
    {
        return $this->properties->get('Id');
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->properties->get('Name');
    }

    /**
     * @inheritDoc
     */
    public function getShareId()
    {
        return $this->properties->get('Id');
    }

    /**
     * @inheritDoc
     */
    public function getTick()
    {
        return $this->properties->get('ShareTick');
    }

    /**
     * @inheritDoc
     */
    public function getParent()
    {
        return null;
    }

    public function getPermissions()
    {
        return $this->properties->get('ShareAssociationType');
    }
}