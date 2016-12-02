#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

source $DIR/checkFileStoragePathIsDefinedAndIsWritable.sh
source $DIR/checkReleaseDirectoryParameter.sh

releaseDir=$1

cd $releaseDir/src/magento/shell
php lizards_and_pumpkins_export.php --blocks
php lizards_and_pumpkins_export.php --all-categories
php lizards_and_pumpkins_export.php --all-products

php $releaseDir/src/lizards-and-pumpkins/bin/triggerTemplateUpdate.php product_listing
