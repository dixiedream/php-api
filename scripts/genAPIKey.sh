#!/bin/sh
set -e

docker run -it --rm --name generate-api-key php:7.4-cli php -r 'echo bin2hex(random_bytes(32)) . "\n";'