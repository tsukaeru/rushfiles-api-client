<?php

namespace Tsukaeru\RushFiles\VirtualFile;

class Share extends Directory
{
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
        return $this;
    }
}