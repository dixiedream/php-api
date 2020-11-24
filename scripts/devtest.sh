#!/bin/sh

docker-compose -f docker-compose.test.yml exec tests phpunit tests