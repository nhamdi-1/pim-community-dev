#!/usr/bin/env bash

currentDir=$(dirname "$0")

echo "Clean previous assets"

rm -rf ${currentDir}/../../app/cache/*
rm -rf ${currentDir}/../../web/cache/*
rm -rf ${currentDir}/../../web/dist/*

echo "Install the assets"

docker-compose exec fpm app/console --env=prod pim:installer:assets --symlink

docker-compose run node npm install
docker-compose run node npm run webpack
