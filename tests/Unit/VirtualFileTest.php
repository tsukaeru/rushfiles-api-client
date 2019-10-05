<?php
use PHPUnit\Framework\TestCase;
use Tsukaeru\RushFiles\VirtualFile;
use Tsukaeru\RushFiles\API\Client;
use org\bovigo\vfs\vfsStream;
use Tsukaeru\RushFiles\VirtualFile\File;
use Tsukaeru\RushFiles\VirtualFile\Directory;
use Tsukaeru\RushFiles\VirtualFile\Share;

class VirtualFileTest extends TestCase
{
    public function testGetChildrenFromDirShare()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('GetDirectoryChildren')
            ->with($this->equalTo('sId'), $this->equalTo('sId'), $this->equalTo('cloudfile.jp'), $this->equalTo('token'))
            ->willReturn([
                ['InternalName' => 'name1', 'PublicName' => 'name1', 'IsFile' => true],
                ['InternalName' => 'name2', 'PublicName' => 'name2', 'IsFile' => true],
            ]);

        $share = new Share(['Id' => 'sId'], 'cloudfile.jp', 'token', $client);

        $files = $share->getChildren();

        $this->assertEquals(2, count($files));
        $this->assertEquals('name1', $files['name1']->getInternalName());
    }

    public function testGetFilesDirectories()
    {
        $client = $this->createMock(Client::class);

        $share = $this->createPartialMock(Directory::class, ['getChildren']);
        $share->method('getChildren')->willReturn(collect([
            'file' => new File(['IsFile' => true, 'InternalName' => 'file'], 'cloudfile.jp', 'token', $client),
            'dir' => new Directory(['IsFile' => false, 'InternalName' => 'dir'], 'cloudfile.jp', 'token', $client),
        ]));

        $files = $share->getFiles();
        $this->assertEquals(1, count($files));
        $this->assertEquals('file', $files['file']->getInternalName());

        $dirs = $share->getDirectories();
        $this->assertEquals(1, count($dirs));
        $this->assertEquals('dir', $dirs['dir']->getInternalName());
    }

    public function testGetContent()
    {
        $client = $this->createMock(Client::class);
        $client->method('GetFileContent')->willReturn('content');

        $file = new File([
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

        $file = new File([
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
        $share = $this->createPartialMock(Directory::class, ['getChildren']);
        $share->method('getChildren')->willReturn(collect([
            'file' => new File(['IsFile' => true, 'InternalName' => 'file'], 'cloudfile.jp', 'token', $client),
            'dir' => new Directory(['IsFile' => false, 'InternalName' => 'dir'], 'cloudfile.jp', 'token', $client),
        ]));

        $files = $share->getContent();
        $this->assertEquals(2, count($files));
    }

    public function testFileSave()
    {
        $filename = 'test.txt';
        $file = $this->createPartialMock(File::class, ['getContent', 'getName', 'isFile']);
        $file->method('getContent')->willReturn('content');
        $file->method('getName')->willReturn($filename);
        $file->method('isFile')->willReturn(true);

        $file_system = vfsStream::setup();

        $path = $file_system->url(). DIRECTORY_SEPARATOR;
        $file->download($path);
        $this->assertFileExists($path . $filename);
        $this->assertEquals('content', file_get_contents($path . $filename));

        $path = $file_system->url() .'/directory/test.txt';
        $file->download($path);
        $this->assertFileExists($path);
        $this->assertEquals('content', file_get_contents($path));
    }

    public function testSaveEmptyFile()
    {
        $file = $this->createPartialMock(File::class, ['getContent', 'isFile', 'getName']);
        $file->method('getContent')->willReturn('');
        $file->method('getName')->willReturn('test.txt');
        $file->method('isFile')->willReturn(true);

        $file_system = vfsStream::setup();

        $path = $file_system->url() .'/test.txt';
        $file->download($path);
        $this->assertFileExists($path);
        $this->assertEquals(0, filesize($path));
    }

    public function testFileSaveDirectory()
    {
        $file1 = $this->createPartialMock(File::class, ['getContent', 'getName', 'isFile']);
        $file1->method('getContent')->willReturn('content');
        $file1->method('getName')->willReturn('test1.txt');
        $file1->method('isFile')->willReturn(true);

        $file2 = $this->createPartialMock(File::class, ['getContent', 'getName', 'isFile']);
        $file2->method('getContent')->willReturn('content');
        $file2->method('getName')->willReturn('test2.txt');
        $file2->method('isFile')->willReturn(true);

        $dir = $this->createPartialMock(Directory::class, ['getChildren', 'getName', 'isFile']);
        $dir->method('getChildren')->willReturn([$file1, $file2]);
        $dir->method('getName')->willReturn('dir');
        $dir->method('isFile')->willReturn(false);

        $file_system = vfsStream::setup();
        $path = $file_system->url() . DIRECTORY_SEPARATOR;
        $dir->download($path);

        $this->assertFileExists($path . 'dir/test1.txt');
        $this->assertFileExists($path . 'dir/test2.txt');
    }

    /**
     * @dataProvider virtualFilesProvider
     */
    public function testGettingProperties($properties, $expected)
    {
        $client = $this->createMock(Client::class);

        $file = VirtualFile::create($properties, 'cloudfile.jp', 'token', $client);

        $this->assertInstanceOf($expected['Class'], $file);
        $this->assertEquals($expected['isFile'], $file->isFile());
        $this->assertEquals($expected['isDirectory'], $file->isDirectory());
        $this->assertEquals($expected['InternalName'], $file->getInternalName());
        $this->assertEquals($expected['ShareId'], $file->getShareId());
        $this->assertEquals($expected['Tick'], $file->getTick());
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
                ],
                [
                    'isFile' => true,
                    'isDirectory' => false,
                    'InternalName' => 'iname',
                    'ShareId' => 'sId',
                    'Tick' => 1,
                    'Class' => File::class,
                ],
            ],
            'directory' => [
                [
                    'IsFile' => false,
                    'InternalName' => 'iname',
                    'ShareId' => 'sId',
                    'Tick' => 1,
                ],
                [
                    'isFile' => false,
                    'isDirectory' => true,
                    'InternalName' => 'iname',
                    'ShareId' => 'sId',
                    'Tick' => 1,
                    'Class' => Directory::class,
                ],
            ],
            'share' => [
                [
                    'Id' => 'id',
                    'ShareTick' => 2,
                    'ShareType' => 0,
                ],
                [
                    'isFile' => false,
                    'isDirectory' => true,
                    'InternalName' => 'id',
                    'ShareId' => 'id',
                    'Tick' => 2,
                    'Class' => Share::class,
                ],
            ],
        ];
    }
}