<?php

namespace React\Tests\Filesystem;

//use React\Tests\Filename\TestCase;
use React\Filesystem\LibuvFilesystem;
use React\Filesystem\FilesystemInterface;
use React\EventLoop;

class LibuvFilesystemTest extends TestCase
{
    private $testdir;

    public function setUp()
    {
        $this->testdir =  sys_get_temp_dir() . DIRECTORY_SEPARATOR . "react-filesystem";
        mkdir($this->testdir);
    }

    private function rmdir_recursive($dir)
    {
        foreach (scandir($dir) as $file) {
            if ('.' != $file && '..' != $file) {
                if (is_dir("$dir/$file"))
                    $this->rmdir_recursive("$dir/$file");
                else
                    unlink("$dir/$file");
            }
        }
        rmdir($dir);
    }

    public function tearDown()
    {
        $this->rmdir_recursive($this->testdir);
    }

    /**
     * @group mkdir
     */
    public function testThatMkdirCreatesADirectory()
    {
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);

        $tests      = $this;
        $directory  = $this->testdir . '/test-mkdir1';

        if (file_exists($directory)) {
           rmdir($directory);
        }

        $callable = $this->createCallableMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($directory);

        $fs
            ->mkdir($directory)
            ->then($callable, $tests->expectCallableNever());

        $loop->run();
        $tests->assertTrue(is_dir($directory));
    }

    /**
     * @group mkdir
     */
    public function testThatMkdirOnAnExistingDirectoryShouldThrowAnException()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $directory = $this->testdir . '/test-mkdir2';

        mkdir($directory);

        $fs
            ->mkdir($directory)
            ->then($tests->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
    }

    /**
     * @dataProvider provideFilePermissions
     * @group mkdir
     */
    public function testThatMkdirRespectsPermissions($mode)
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $directory = $this->testdir . '/test-mkdir3';

        $callable = $this->createCallableMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($directory);

        $fs
            ->mkdir($directory, $mode)
            ->then($callable, $this->expectCallableNever());
        $loop->run();
        $this->assertTrue(is_dir($directory));

        if (file_exists($directory)) {
            rmdir($directory);
        }
    }

    /**
     * @dataProvider provideFilePermissions
     * @group mkdir
     */
    public function testThatOpenRespectsPermissions($mode)
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $path = $this->testdir . '/test-open1';

        if (file_exists($path)) {
            unlink($path);
        }

        $callable = $this->createCallableMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->logicalNot($this->isInstanceOf("Exception")));

        $fs
            ->open($path, FilesystemInterface::FLAG_WRONLY | FilesystemInterface::FLAG_CREAT, $mode)
            ->then($callable, $this->expectCallableNever());
        $loop->run();
        $this->assertEquals(decoct($mode), $this->getFileMode($path));

    }

    public function provideFilePermissions()
    {
        return array(
            array(0755),
            array(0111),
            array(0555),
        );
    }

    private function getFileMode($file)
    {
        return substr(decoct(fileperms($file)), -4);
    }

    /**
     * @group rmdir
     */
    public function testThatRmdirRemovesADirectory()
    {
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);

        $tests      = $this;
        $directory  = $this->testdir . '/test-mkdir1';

        if (!file_exists($directory)) {
           mkdir($directory);
        }

        $callable = $this->createCallableMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($directory);

        $fs
            ->rmdir($directory)
            ->then($callable, $tests->expectCallableNever());

        $loop->run();
        $tests->assertTrue(!is_dir($directory));
    }

    /**
     * @group rmdir
     */
    public function testThatRmdirRemovesADirectoryError()
    {
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);

        $tests      = $this;
        $directory   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'unknown_dir';

        $fs
            ->rmdir($directory)
            ->then($tests->expectCallableNever(), $this->expectCallableOnce());

        $loop->run();
    }

    /**
     * @group scandir
     */
    public function testScandirReturnsDirectoryList()
    {
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);

        $result = null;
        $fs
            ->scandir(sys_get_temp_dir())
            ->then(function($contents) use (&$result) {
                $result = $contents;
            }, $this->expectCallableNever());

        $loop->run();

        $this->assertTrue(in_array('react-filesystem', $result));
    }

    /**
     * @group open
     */
    public function testThatOpenOpensAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $path = $this->testdir . '/test-open2';

        $fs
            ->open($path, FilesystemInterface::FLAG_WRONLY | FilesystemInterface::FLAG_CREAT, 0664)
            ->then($tests->expectCallableOnce(), $this->expectCallableNever());
        $loop->run();
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * @group open
     */
    public function testThatOpenDoesNotOpenAnUnexistingFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $path = $this->testdir . '/unexisting-file';

        $fs
            ->open($path, FilesystemInterface::FLAG_WRONLY, 0664)
            ->then($tests->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
    }

    /**
     * @group open
     */
    public function testThatOpenThrowsAnExceptionWhenPermissionsAreSetToNull()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);

        $tests = $this;
        $path = $this->testdir . '/test-file-create';

        touch($path);
        chmod($path, 0000);

        $fs
            ->open($path, FilesystemInterface::FLAG_WRONLY, 0064)
            ->then($tests->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();

        unlink($path);
    }

    /**
     * @group write
     */
    public function testThatWriteCanWriteInAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-write1';
        $testbuffer = "testwrite";

        $fs
            ->open($path, FilesystemInterface::FLAG_WRONLY | FilesystemInterface::FLAG_CREAT)->then(function($result) use ($fs, $testbuffer) {
                $fs->write($result, $testbuffer);
            });
        $loop->run();
        $this->assertEquals(file_get_contents($path), $testbuffer);
    }

    /**
     * @group write
     */
    public function testThatWriteCannotWriteInAFileOpenForReadingOnly()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-write2';
        $testbuffer = "testwrite";

        $fs
            ->open($path, FilesystemInterface::FLAG_RDONLY | FilesystemInterface::FLAG_CREAT)->then(function($result) use ($fs, $testbuffer) {
                $fs->write($result, $testbuffer);
            });
        $loop->run();
        $this->assertEquals(file_get_contents($path), '');
    }

    /**
     * @group stat
     */
    public function testThatStatDoesNotWorkOnUnexistingFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-stat1';

        $fs->stat($path)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());
        $loop->run();
    }

    /**
     * @group fstat
     */
    public function testThatFStatReturnsFileStat()
    {
        $file   = tempnam($this->testdir, 'unit-');
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);

        $stat = null;
        $fs->open($file)
            ->then(function($fd) use ($fs, &$stat) {
                $fs->fstat($fd)
                    ->then(function($data) use (&$stat) {
                        $stat = $data;
                    }, $this->expectCallableNever());
            }, $this->expectCallableNever());

        $loop->run();

        $this->assertTrue(!empty($stat));
    }

    /**
     * @group fstat
     */
    public function testThatFStatFailsFileStat()
    {
        $file   = tempnam($this->testdir, 'unit-');
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);

        $fs->open($file)
            ->then(function($fd) use ($fs, &$stat) {
                $fs->fstat(-4)
                    ->then($this->expectCallableNever(), $this->expectCallableOnce());
            }, $this->expectCallableNever());

        $loop->run();
    }

    /**
     * @group read
     */
    public function testThatReadCanReadFromAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-read1';
        $testbuffer = "testread";

        $buffer = null;

        file_put_contents($path, $testbuffer);
        $fs
            ->open($path)->then(function($result) use ($fs, $testbuffer, &$buffer) {
                $fs->read($result, strlen($testbuffer))->then(function($result) use (&$buffer) {
                    $buffer = $result;
                });
            });
        $loop->run();
    }

    /**
     * @group read
     */
    public function testThatReadfileCanReadFromAFile()
    {
         $loop = new EventLoop\LibUvLoop();
         $fs = new LibuvFilesystem($loop);
         $path = $this->testdir . '/test-readfile1';
         $testbuffer = "test2";
         $callable = $this->createCallableMock();
         $callable
             ->expects($this->once())
             ->method('__invoke')
             ->with($testbuffer);

         file_put_contents($path, $testbuffer);
         $fs->readFile($path)
             ->then($callable, $this->expectCallableNever());
         $loop->run();
    }

    /**
     * @group read
     */
    public function testThatReadfileCannotReadAnUnexistingFile()
    {
         $loop = new EventLoop\LibUvLoop();
         $fs = new LibuvFilesystem($loop);
         $path = $this->testdir . '/test-readfile2';

         $fs->readFile($path)
             ->then($this->expectCallableNever(), $this->expectCallableOnce());
         $loop->run();
    }

    /**
     * @group stat
     */
    public function testThatStatReturnsStatsOfAFile()
    {
        $loop = new EventLoop\LibUvLoop();
        $fs = new LibuvFilesystem($loop);
        $path = $this->testdir . '/test-stat2';
        $testbuffer = "test";
        $tests = $this;

        $catchResult = null;

        file_put_contents($path, $testbuffer);
        $fs->stat($path)
            ->then(function($result) use (&$catchResult) {
                $catchResult = $result;
            }, $this->expectCallableNever());

        $loop->run();
        $tests->assertEquals(4, $catchResult['size']);
    }

    /**
     * @group rename
     */
    function testThatCanRenameFile()
    {
        $file   = tempnam($this->testdir, 'unit-');
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);
        $newfile = $this->testdir . DIRECTORY_SEPARATOR . 'ohhaiz.txt';
        $result = null;

        $fs->rename($file, $newfile)
            ->then(function($path) use (&$result)  {
                $result = $path;
        }, $this->expectCallableNever());

        if(!file_exists($newfile)) {
            $this->fail();
        }

        $loop->run();
        $this->assertEquals($newfile, $result);

    }


    /**
     * @group rename
     */
    function testThatCanRenameFileThrowsException()
    {
        $file   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'unknown_file';
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);
        $newfile = $this->testdir . DIRECTORY_SEPARATOR . 'ohhaiz.txt';
        $result = null;

        $fs->rename($file, $newfile)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());

        $loop->run();

    }

    /**
     * @group chmod
     */
    function testThatCanChmodFile()
    {
        $file   = tempnam($this->testdir, 'unit-');
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);
        $result = null;

        chmod($file, 0644);
        clearstatcache();

        $fs->chmod($file, 0755)
            ->then(function($path) use (&$result)  {
                $result = 'ok';
        }, $this->expectCallableNever());

        $loop->run();

        $perms = fileperms($file);

        $this->assertEquals($result, 'ok');
        $this->assertEquals(substr(sprintf('%o', fileperms($file)), -4), "0755");

    }

    /**
     * @group chmod
     */
    function testThatCanChmodFileThrowsExceptionInvalidPath()
    {
        $file   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'unknown_file';
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);
        $result = null;

        $fs->chmod($file, 0755)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());

        $loop->run();

    }

    /**
     * @group unlink
     */
    function testThatCanUnlinkExistingFile()
    {
        $file   = tempnam($this->testdir, 'unit-');
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);
        $result = null;

        $fs->unlink($file)
            ->then($this->expectCallableOnce(), $this->expectCallableNever());

        $loop->run();
    }


    /**
     * @group unlink
     */
    function testThatCanUnlinkExistingFileError()
    {
        $file   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'unknown_file';
        $loop   = new EventLoop\LibUvLoop();
        $fs     = new LibuvFilesystem($loop);
        $result = null;

        $fs->unlink($file)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());

        $loop->run();
    }
}