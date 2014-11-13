#!/bin/bash
set -e
set -o pipefail

if [[ "$TRAVIS_PHP_VERSION" != "hhvm" &&
      "$TRAVIS_PHP_VERSION" != "hhvm-nightly" ]]; then

    # install 'libuv'
    git clone --recursive --branch v1.0.0-rc2 --depth 1 https://github.com/joyent/libuv
    pushd libuv
    ./autogen.sh && ./configure && make && sudo make install
    popd

    #install 'php-uv'
    git clone --recursive --branch libuv-1.0 --depth 1 https://github.com/steverhoades/php-uv
    pushd php-uv
    phpize && ./configure --with-uv --enable-httpparser && make && sudo make install
    echo "extension=uv.so" >> "$(php -r 'echo php_ini_loaded_file();')"
    popd

fi

composer install --dev --prefer-source
