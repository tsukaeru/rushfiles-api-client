<?php

namespace Tsukaeru\RushFiles\VirtualFile;

class Share extends Directory
{
    /**
     * @inheritDoc
     */
    public function getInternalName()
    {
        return $this->properties['Id'];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->properties['Name'];
    }

    /**
     * @inheritDoc
     */
    public function getShareId()
    {
        return $this->properties['Id'];
    }

    /**
     * @inheritDoc
     */
    public function getTick()
    {
        return $this->properties['ShareTick'];
    }

    /**
     * @inheritDoc
     */
    public function getParent()
    {
        return null;
    }
}