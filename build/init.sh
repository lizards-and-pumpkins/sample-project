#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

source "$DIR/checkReleaseDirectoryParameter.sh"

releaseDir=$1
shareDir=${releaseDir}/share

currentDir="$(pwd)"

cd ${releaseDir} && composer install --no-interaction --no-progress

cd ${releaseDir}/src/magento && modman init > /dev/null 2>&1
ls -1d ${releaseDir}/src/magento-extensions/* | xargs -I % modman link %  > /dev/null; cd ${releaseDir}/src/magento
ls -1d ${releaseDir}/src/magento-themes/* | xargs modman link > /dev/null
modman repair --force > /dev/null && echo Modman links processed

cd ${releaseDir}/src/magento/.modman/magento-connector/ && composer install --no-interaction --no-progress

ln -fsT ${shareDir}/local.xml ${releaseDir}/src/magento/app/etc/local.xml

ln -fsT ${releaseDir}/src/magento/errors ${releaseDir}/pub/errors
ln -fsT ${releaseDir}/src/magento/js ${releaseDir}/pub/mage-js
ln -fsT ${shareDir}/media ${releaseDir}/pub/media
ln -fsT ${releaseDir}/src/magento/skin ${releaseDir}/pub/skin

ln -fsT ${releaseDir}/src/lizards-and-pumpkins/pub/css ${releaseDir}/pub/css
ln -fsT ${releaseDir}/src/lizards-and-pumpkins/pub/js ${releaseDir}/pub/js
ln -fsT ${releaseDir}/src/lizards-and-pumpkins/pub/images ${releaseDir}/pub/images

rm -fr ${releaseDir}/src/magento/media; ln -fsT ${shareDir}/media ${releaseDir}/src/magento/media
#rm -rf ${releaseDir}/src/var && ln -fsT ${varDir} ${releaseDir}/src/var # TODO: Resolve

cd "$currentDir"

echo Symlinks created

