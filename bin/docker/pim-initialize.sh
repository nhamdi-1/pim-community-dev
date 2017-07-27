#!/usr/bin/env bash

currentDir=$(dirname "$0")

echo "Clean previous install"

rm -rf ${currentDir}/../../app/cache/*
rm -rf ${currentDir}/../../app/logs/*
rm -rf ${currentDir}/../../web/cache/*
rm -rf ${currentDir}/../../web/dist/*

echo "Install the PIM database"

docker-compose exec fpm app/console --env=prod pim:install --force --symlink
docker-compose exec fpm app/console --env=behat pim:installer:db

echo "Install the assets"

docker-compose run node npm install
docker-compose run node npm run webpack
