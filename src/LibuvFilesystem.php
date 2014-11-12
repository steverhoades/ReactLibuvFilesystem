<?php

namespace React\Filesystem;

use React\EventLoop\LibUvLoop;
use React\Filesystem\FlagConverter\LibUvFlagConverter;
use React\Filesystem\Exception\IoException;
use React\Promise\Deferred;
use React\Promise;

class LibuvFilesystem implements FilesystemInterface
{
    private $loop;
    private $converter;

    /**
     * Construct the filesystem object
     *
     * @param LibUvLoop $loop [description]
     */
    public function __construct(LibUvLoop $loop)
    {
        if (!$loop instanceof LibUvLoop) {
            throw new \InvalidArgumentException('Argument 1 should be an instance of LibUvLoop.');
        }

        $this->loop      = $loop;
        $this->converter = new LibUvFlagConverter();
    }

    /**
     * mkdir
     *
     * @param  string  $dirname directory name
     * @param  integer $mode    directory permissions
     * @return React\Promise    Returns a promise
     */
    public function mkdir($dirname, $mode = 0755)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result) use ($deferred, $dirname) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($dirname);
        });

        uv_fs_mkdir($loop, $dirname, $mode, $callback);

        return $deferred->promise();
    }

    /**
     * rmdir
     *
     * @param  string           $dirname    Directory path
     * @return React\Promise                Returns a promise
     */
    public function rmdir($dirname)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result) use ($deferred, $dirname) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($dirname);
        });

        uv_fs_rmdir($loop, $dirname, $callback);

        return $deferred->promise();
    }

    /**
     * scandir - returns a list of files, directories for a given path
     *
     * @param  string           $directory Directory path
     * @return React\Promise               Returns a Promise
     */
    public function scandir($directory)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($fd, $result) use ($deferred) {
            if ($fd < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($result);
        });

        uv_fs_scandir($loop, $directory, FilesystemInterface::FLAG_RDONLY, $callback);

        return $deferred->promise();
    }

    /**
     * open a file descriptor
     *
     * @param  string    $path  Path to the file
     * @param  bit       $flags Flags
     * @param  integer   $mode  permissions
     * @return React\Promise    Returns a Promise
     */
    public function open($path, $flags = FilesystemInterface::FLAG_RDONLY, $mode = 0755)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;
        $flags = $this->converter->convertFlags($flags);


        $callback = $this->loop->taskCallback(function ($r) use ($deferred, $path) {
            if ($r < 0) {
                $deferred->reject($this->createIoException($r));
                return;
            }
            $deferred->resolve($r);
        });

        uv_fs_open($loop, $path, $flags, $mode, $callback);

        return $deferred->promise();
    }

    /**
     * write to a file descriptor
     *
     * @param  int              $fd     file descriptor id
     * @param  string           $buffer the buffer to write
     * @param  integer          $offset the offset value
     * @return React\Promise            Returns a Promise
     */
    public function write($fd, $buffer, $offset = 0)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($stream, $result) use ($deferred) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($result);
        });

        uv_fs_write($loop, $fd, $buffer, $offset, $callback);

        return $deferred->promise();
    }

    /**
     * close file descriptor
     *
     * @param  int              $fd     file descriptor id
     * @return React\Promise            Returns a Promise
     */
    public function close($fd)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result) use ($deferred) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($result);
        });

        uv_fs_close($loop, $fd, $callback);

        return $deferred->promise();
    }

    /**
     * read from a file descriptor
     *
     * @param  int              $fd         file descriptor id
     * @param  int              $length     length to read
     * @return React\Promise                Returns a Promise
     */
    public function read($fd, $length)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($r, $nbread, $buffer) use ($deferred, $loop) {
            if ($nbread <= 0) {
                $deferred->reject($this->createIoException($r));
                return;
            }
            $deferred->resolve($buffer);
        });

        uv_fs_read($loop, $fd, $length, $callback);

        return $deferred->promise();
    }

    /**
     * rename a file
     *
     * @param  string           $from   path
     * @param  string           $to     path
     * @return React\Promise            Returns a Promise
     */
    public function rename($from, $to)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result) use ($deferred, $to) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($to);
        });

        uv_fs_rename($loop, $from, $to, $callback);

        return $deferred->promise();
    }

    /**
     * chmod
     *
     * @param  string           $path   path
     * @param  int              $mode   permissions
     * @return React\Promise            Returns a Promise
     */
    public function chmod($path, $mode)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result) use ($deferred, $path) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($path);
        });

        uv_fs_chmod($loop, $path, $mode, $callback);

        return $deferred->promise();
    }

    /**
     * chown
     *
     * @param  string           $path   path
     * @param  int              $uid    uid
     * @param  int              $guid   guid
     * @return React\Promise            Returns a Promise
     */
    public function chown($path, $uid, $guid)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result) use ($deferred, $path) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($path);
        });

        uv_fs_chown($loop, $path, $uid, $guid, $callback);

        return $deferred->promise();
    }

    /**
     * unlink
     *
     * @param  string           $path   path
     * @return React\Promise            Returns a Promise
     */
    public function unlink($path)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result) use ($deferred, $path) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($path);
        });

        uv_fs_unlink($loop, $path, $callback);

        return $deferred->promise();
    }

    /**
     * ftruncate
     *
     * @param  int              $fd         file descriptor id
     * @param  int              $offset     offset
     * @return React\Promise                Returns a Promise
     */
    public function ftruncate($fd, $offset)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result) use ($deferred, $path) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($path);
        });

        uv_fs_fruncate($loop, $fd, $offset, $callback);

        return $deferred->promise();
    }

    /**
     * stat
     *
     * @param  string           $filename   filename
     * @return React\Promise                Returns a Promise
     */
    public function stat($filename)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result, $stat) use ($deferred) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($stat);
        });

        uv_fs_stat($loop, $filename, $callback);

        return $deferred->promise();
    }

    /**
     * fstat
     *
     * @param  int              $fd     file descriptor id
     * @return React\Promise            Returns a Promise
     */
    public function fstat($fd)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result, $stat) use ($deferred) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($stat);
        });

        uv_fs_fstat($loop, $fd, $callback);

        return $deferred->promise();
    }

    /**
     * readFile
     *
     * @param  string           $filename   filename
     * @return React\Promise                Returns a Promise
     */
    public function readFile($filename)
    {
        $fs = $this;

        $all = array(
            'stat' => $fs->stat($filename),
            'fd'   => $fs->open($filename)
        );

        return Promise\all($all)->then(function ($result) use ($fs) {
            $fd = $result['fd'];

            return $fs->read($fd, $result['stat']['size'])->then(function ($data) use ($fs, $fd) {
                $fs->close($fd);
                return $data;
            });
        });
    }

    /**
     * sendfile
     *
     * @param  int              $fdIn       incoming file descriptor id
     * @param  int              $fdOut      outgoing file descriptor id
     * @param  int              $offset     offset
     * @param  int              $length     length
     * @return React\Promise                Returns a Promise
     */
    public function sendfile($fdIn, $fdOut, $offset, $length)
    {
        $deferred = new Deferred();
        $loop = $this->loop->loop;

        $callback = $this->loop->taskCallback(function ($result) use ($deferred, $path) {
            if ($result < 0) {
                $deferred->reject($this->createIoException($result));
                return;
            }
            $deferred->resolve($path);
        });

        uv_fs_sendfile($loop, $inFd, $outFd, $offset, $length, $callback);

        return $deferred->promise();
    }

    /**
     * create error message based on passed uv error code
     *
     * @param  int          $code   error code provided by uv
     * @return IoException
     */
    private function createIoException($code)
    {
        return new IoException(uv_strerror($code));
    }
}
