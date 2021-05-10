#! /bin/sh
set -e

composefile=${2:-docker-compose.yml}

docker-compose -f "$composefile" exec -u $(id -u):$(id -g) server vendor/bin/propel $1