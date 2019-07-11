<?php

namespace Tsukaeru\RushFiles\VirtualFile;

class Share extends Directory
{
    public function getInternalName()
    {
        return $this->properties['Id'];
    }

    public function getName()
    {
        return $this->properties['Name'];
    }

    public function getShareId()
    {
        return $this->properties['Id'];
    }

    public function getTick()
    {
        return $this->properties['ShareTick'];
    }

    public function getParent()
    {
        return null;
    }
}