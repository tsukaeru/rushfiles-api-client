<?php
use PHPUnit\Framework\TestCase;
use Tsukaeru\RushFiles\VirtualFile;
use Tsukaeru\RushFiles\Client;

class VirtualFileTest extends TestCase
{
    public function testGetChildrenFromDirShare()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('GetDirectoryChildren')
            ->with($this->equalTo('sId'), $this->equalTo('sId'), $this->equalTo('cloudfile.jp'), $this->equalTo('token'))
            ->willReturn([
                ['InternalName' => 'name1'],
                ['InternalName' => 'name2'],
            ]);

        $share = new VirtualFile(['Id' => 'sId'], 'cloudfile.jp', 'token', $client);

        $files = $share->getChildren();

        $this->assertEquals(2, count($files));
        $this->assertEquals('name1', $files['name1']->getInternalName());
    }

    public function testGetFilesDirectories()
    {
        $client = $this->createMock(Client::class);

        $share = $this->createPartialMock(VirtualFile::class, ['getChildren']);
        $share->method('getChildren')->willReturn(collect([
            'file' => new VirtualFile(['IsFile' => true, 'InternalName' => 'file'], 'cloudfile.jp', 'token', $client),
            'dir' => new VirtualFile(['IsFile' => false, 'InternalName' => 'dir'], 'cloudfile.jp', 'token', $client),
        ]));

        $files = $share->getFiles();
        $this->assertEquals(1, count($files));
        $this->assertEquals('file', $files->first()->getInternalName());

        $dirs = $share->getDirectories();
        $this->assertEquals(1, count($dirs));
        $this->assertEquals('dir', $dirs->first()->getInternalName());

    }

    /**
     * @dataProvider virtualFilesProvider
     */
    public function testGettingProperties($properties, $expected)
    {
        $client = $this->createMock(Client::class);

        $file = new VirtualFile($properties, 'cloudfile.jp', 'token', $client);

        $this->assertEquals($expected['isFile'], $file->isFile());
        $this->assertEquals($expected['isDirectory'], $file->isDirectory());
        $this->assertEquals($expected['InternalName'], $file->getInternalName());
        $this->assertEquals($expected['ShareId'], $file->getShareId());
        $this->assertEquals($expected['Tick'], $file->getTick());
        $this->assertEquals($expected['ShareTick'], $file->getShareTick());
    }

    public function virtualFilesProvider()
    {
        return [
            'file' => [
                [
                    'IsFile' => true,
                    'InternalName' => 'iname',
                    'ShareId' => 'sId',
                    'Tick' => 1,
                    'ShareTick' => 2,
                ],
                [
                    'isFile' => true,
                    'isDirectory' => false,
                    'InternalName' => 'iname',
                    'ShareId' => 'sId',
                    'Tick' => 1,
                    'ShareTick' => 2,
                ],
            ],
            'directory' => [
                [
                    'IsFile' => false,
                    'InternalName' => 'iname',
                    'ShareId' => 'sId',
                    'Tick' => 1,
                    'ShareTick' => 2,
                ],
                [
                    'isFile' => false,
                    'isDirectory' => true,
                    'InternalName' => 'iname',
                    'ShareId' => 'sId',
                    'Tick' => 1,
                    'ShareTick' => 2,
                ],
            ],
            'share' => [
                [
                    'Id' => 'id',
                    'ShareTick' => 2,
                ],
                [
                    'isFile' => false,
                    'isDirectory' => true,
                    'InternalName' => 'id',
                    'ShareId' => 'id',
                    'Tick' => 2,
                    'ShareTick' => 2,
                ],
            ],
        ];
    }
}