#!/bin/bash

if [[ "$1" != /* ]]; then
  echo "Absolute Release directory path must be specified as script argument (e.g. init.sh /path/)."
  exit 1
fi

if [ ! -d "$1" ]; then
  echo "Release directory $1 does not exist."
  exit 1
fi

releaseDir=$1
shareDir=${releaseDir}/share

cd ${releaseDir} && composer install --no-interaction

cd ${releaseDir}/src/magento && modman init > /dev/null 2>&1
ls -1d ${releaseDir}/src/magento-extensions/* | xargs -I % modman link %  > /dev/null; cd ${releaseDir}/src/magento
ls -1d ${releaseDir}/src/magento-themes/* | xargs modman link > /dev/null
modman repair --force > /dev/null && echo Modman links processed

cd ${releaseDir}/src/magento/.modman/magento-connector/ && composer install --no-interaction

ln -fsT ${shareDir}/local.xml ${releaseDir}/src/magento/app/etc/local.xml

ln -fsT ${releaseDir}/src/magento/errors ${releaseDir}/pub/errors
ln -fsT ${releaseDir}/src/magento/js ${releaseDir}/pub/mage-js
ln -fsT ${shareDir}/media ${releaseDir}/pub/media
ln -fsT ${releaseDir}/src/magento/skin ${releaseDir}/pub/skin

#ln -fsT ${releaseDir}/vendor/lizards-and-pumpkins/catalog/pub/css ${releaseDir}/pub/css
#ln -fsT ${releaseDir}/vendor/lizards-and-pumpkins/catalog/pub/js ${releaseDir}/pub/js
#ln -fsT ${releaseDir}/vendor/lizards-and-pumpkins/catalog/pub/images ${releaseDir}/pub/images
#ln -fsT ${releaseDir}/vendor/lizards-and-pumpkins/catalog/pub/fonts ${releaseDir}/pub/fonts

rm -fr ${releaseDir}/src/magento/media; ln -fsT ${shareDir}/media ${releaseDir}/src/magento/media
#rm -rf ${releaseDir}/src/var && ln -fsT ${varDir} ${releaseDir}/src/var

echo Symlinks created
