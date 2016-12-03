#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

source "$DIR/checkReleaseDirectoryParameter.sh"

releaseDir=$1
currentDir="$(pwd)"

cd ${releaseDir} && composer install --no-interaction --no-progress

cd ${releaseDir}/src/magento && modman init > /dev/null 2>&1


cd .modman
for ext in $(ls -1d ${releaseDir}/src/magento-extensions/*); do
    ln -s ../../magento-extensions/$(basename $ext)
done

for theme in $(ls -1d ${releaseDir}/src/magento-themes/*); do
    ln -s ../../magento-themes/$(basename $theme)
done
cd ..

modman repair --force > /dev/null && echo Modman links processed

cd ${releaseDir}/src/magento/.modman/magento-connector/ && composer install --no-interaction --no-progress

ln -fsT ../../../../share/local.xml ${releaseDir}/src/magento/app/etc/local.xml

ln -fsT ../src/magento/errors ${releaseDir}/pub/errors
ln -fsT ../src/magento/js ${releaseDir}/pub/mage-js
ln -fsT ../src/magento/skin ${releaseDir}/pub/skin
ln -fsT ../share/media ${releaseDir}/pub/media

ln -fsT ../src/lizards-and-pumpkins/pub/css ${releaseDir}/pub/css
ln -fsT ../src/lizards-and-pumpkins/pub/js ${releaseDir}/pub/js
ln -fsT ../src/lizards-and-pumpkins/pub/images ${releaseDir}/pub/images

rm -fr ${releaseDir}/src/magento/media;
ln -fsT ../../share/media ${releaseDir}/src/magento/media

cd "$currentDir"

echo Symlinks created

