#!/bin/sh

echo ${LIBRDKAFKA_VERSION}

git clone --depth 1 --branch "${LIBRDKAFKA_VERSION}" https://github.com/edenhill/librdkafka.git
(
    cd librdkafka
    ./configure
    make
    sudo make install
)
sudo ldconfig

pecl install rdkafka-4.0.0
