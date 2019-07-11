<?php

namespace Tsukaeru\RushFiles\DTO;

use Ramsey\Uuid\Uuid;

class ClientJournal extends BaseDTO
{
    const CREATE               = 0;
    const DELETE               = 1;
    const RENAME               = 2;
    const UPDATE               = 3;
    const FILE_ATTRIBUTES      = 4;
    const CATCH_UP             = 5;
    const DUPLICATE            = 6;
    const CONFLICT_NEW_VERSION = 6;
    const FILE_NOT_PRESENT     = 8;
    const IGNORE_DELETE        = 9;
    const RENAME_CONFLICT      = 10;
    const RECOVER              = 11;
    const UNDELETE             = 12;
    const LOCK_FILE            = 13;
    const UNLOCK_FILE          = 14;

    public function __construct(RfVirtualFile $RfVirtualFile, $EventType, $DeviceId, $properties = [])
    {
        if ($EventType < 0 || $EventType > 14) {
            throw new \InvalidArgument("EventType must be an enum between 0 and 14.");
        }

        $this->properties = array_merge([
            'RfVirtualFile' => $RfVirtualFile,
            'TransmitId' => Uuid::uuid1(),
            'ClientJournalEventType' => $EventType,
            'DeviceId' => $DeviceId,
        ], $properties);
    }
}