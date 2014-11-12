ReactLibuvFilesystem
====================

libuv Filesystem functions to be used with the ReactPHP Event Loop library

This library requires that the php-uv extension is compiled with PHP.  Currently this is targeting a WIP of libuv 1.0 support for php-uv availabel here: https://github.com/steverhoades/php-uv.git#branch=libuv-1.0

Installation
```
git clone --recursive --branch libuv-1.0 http://github.com/steverhoades/php-uv.git
cd php-uv
phpize
./configure --with-uv --enable-http-parser && make && make test && sudo make install


