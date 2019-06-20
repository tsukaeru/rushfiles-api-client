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

    public function testGetContent()
    {
        $client = $this->createMock(Client::class);
        $client->method('GetFileContent')->willReturn('content');

        $file = new VirtualFile([
            'IsFile' => true,
            'InternalName' => 'iname',
            'UploadName' => 'uname',
            'ShareId' => 'sId',
            'Tick' => 1,
            'ShareTick' => 2,
            'EndOfFile' => 42,
        ], 'cloudfile.jp', 'token', $client);

        $content = $file->getContent();

        $this->assertEquals('content', $content);
    }

    public function testGetContentEmpty()
    {
        $client = $this->createMock(Client::class);
        $client->method('GetFileContent')->willReturn('content');

        $file = new VirtualFile([
            'IsFile' => true,
            'InternalName' => 'iname',
            'ShareId' => 'sId',
            'Tick' => 1,
            'ShareTick' => 2,
            'EndOfFile' => 0,
        ], 'cloudfile.jp', 'token', $client);

        $content = $file->getContent();

        $this->assertEquals('', $content);
    }

    public function testGetContentDirectory()
    {
        $client = $this->createMock(Client::class);
        $share = $this->createPartialMock(VirtualFile::class, ['getChildren']);
        $share->method('getChildren')->willReturn(collect([
            'file' => new VirtualFile(['IsFile' => true, 'InternalName' => 'file'], 'cloudfile.jp', 'token', $client),
            'dir' => new VirtualFile(['IsFile' => false, 'InternalName' => 'dir'], 'cloudfile.jp', 'token', $client),
        ]));

        $files = $share->getContent();
        $this->assertEquals(2, count($files));
    }

    public function testFileSave()
    {
        $file = $this->createPartialMock(VirtualFile::class, ['getContent', 'getName', 'isFile']);
        $file->method('getContent')->willReturn('content');
        $file->method('getName')->willReturn('test.txt');
        $file->method('isFile')->willReturn(true);

        $file_system = vfsStream::setup();

        $file->save($file_system->url().'/');
        $this->assertFileExists($file_system->url() . '/test.txt');
        $this->assertEquals('content', file_get_contents($file_system->url().'/test.txt'));

        $path = $file_system->url() .'/directory/test.txt';
        $file->save($path);
        $this->assertFileExists($path);
        $this->assertEquals('content', file_get_contents($path));
    }

    public function testSaveEmptyFile()
    {
        $file = $this->createPartialMock(VirtualFile::class, ['getContent', 'isFile', 'getName']);
        $file->method('getContent')->willReturn('');
        $file->method('getName')->willReturn('test.txt');
        $file->method('isFile')->willReturn(true);

        $file_system = vfsStream::setup();

        $path = $file_system->url() .'/test.txt';
        $file->save($path);
        $this->assertFileExists($path);
        $this->assertEquals(0, filesize($path));
    }

    public function testFileSaveDirectory()
    {
        $file1 = $this->createPartialMock(VirtualFile::class, ['getContent', 'getName', 'isFile']);
        $file1->method('getContent')->willReturn('content');
        $file1->method('getName')->willReturn('test1.txt');
        $file1->method('isFile')->willReturn(true);

        $file2 = $this->createPartialMock(VirtualFile::class, ['getContent', 'getName', 'isFile']);
        $file2->method('getContent')->willReturn('content');
        $file2->method('getName')->willReturn('test2.txt');
        $file2->method('isFile')->willReturn(true);

        $dir = $this->createPartialMock(VirtualFile::class, ['getChildren', 'getName', 'isFile']);
        $dir->method('getChildren')->willReturn([$file1, $file2]);
        $dir->method('getName')->willReturn('dir');
        $dir->method('isFile')->willReturn(false);

        $file_system = vfsStream::setup();
        $path = $file_system->url() . DIRECTORY_SEPARATOR;
        $dir->save($path);

        $this->assertFileExists($path . 'dir/test1.txt');
        $this->assertFileExists($path . 'dir/test2.txt');
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