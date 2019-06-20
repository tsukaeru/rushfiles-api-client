<?php

namespace Tsukaeru\RushFiles\VirtualFile;

class Share extends Directory
{
    public function getInternalName(): string
    {
        return $this->properties['Id'];
    }

    public function getName(): string
    {
        return $this->properties['Name'];
    }

    public function getShareId(): string
    {
        return $this->properties['Id'];
    }

    public function getTick(): int
    {
        return $this->properties['ShareTick'];
    }
}