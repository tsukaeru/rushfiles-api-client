<?php
use PHPUnit\Framework\TestCase;
use Tsukaeru\RushFiles\API\Client;
use Tsukaeru\RushFiles\PublicLink;
use Tsukaeru\RushFiles\VirtualFile\File;

class PublicLinkTest extends TestCase
{
    function testCreatingURLString()
    {
        $client = $this->createMock(Client::class);
        $client->method('CreatePublicLink')->willReturn('https://cloudfile.jp/client/publiclink.aspx?id=NiB0i6QIQX');

        $file = new File([
            'IsFile' => true,
            'InternalName' => 'iname',
            'UploadName' => 'uname',
            'ShareId' => 'sId',
            'Tick' => 1,
            'ShareTick' => 2,
            'EndOfFile' => 42,
        ], 'cloudfile.jp', 'token', $client);

        $linkStr = $file->createPublicLink(PublicLink::STRING);

        $this->assertRegExp('/^https\:\/\/cloudfile\.jp\/client\/publiclink\.aspx\?id=[\w]{10}$/', $linkStr);
    }
}