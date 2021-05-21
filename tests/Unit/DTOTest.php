<?php

use PHPUnit\Framework\TestCase;
use Tsukaeru\RushFiles\API\DTO\ClientJournal;
use Tsukaeru\RushFiles\API\DTO\RfVirtualFile;

class DTOTest extends TestCase
{
    public function testThrowsOnUnknownEventType()
    {
        $this->expectException(InvalidArgumentException::class);

        new ClientJournal(new RfVirtualFile('share_id', 'parent_id', []), 15, 'device_id');
    }

    public function testThrowsOnIncorrectFileArgument()
    {
        $this->expectException(InvalidArgumentException::class);

        new RfVirtualFile('share_id', 'parent_id', 'non/existing/file');
    }

    public function testRfVirtualFileProperties()
    {
        $rfFile = new RfVirtualFile('share_id', 'parent_id', []);
        $this->assertEquals('parent_id', $rfFile->getParentId());
    }
}