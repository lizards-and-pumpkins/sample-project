#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

use LizardsAndPumpkins\Util\Config\Exception\EnvironmentConfigKeyIsNotSetException;
use LizardsAndPumpkins\Util\Factory\CatalogMasterFactory;
use LizardsAndPumpkins\Util\Factory\CommonFactory;
use LizardsAndPumpkins\Util\Factory\ProjectFactory;

require_once __DIR__ . '/../../../vendor/autoload.php';


function getFileStorageBasePathConfig() {
    try {
        $factory = new CatalogMasterFactory();
        $factory->register(new CommonFactory());
        $factory->register(new ProjectFactory());

        return $factory->getFileStorageBasePathConfig();
    } catch (EnvironmentConfigKeyIsNotSetException $exception) {
        return '[path to file storage base directory]';
    }
}

printf("WARNING:\nThis command is deprecated! Instead, use the native shell command:\n");
printf('rm -r "%s"', getFileStorageBasePathConfig());
printf("\n\n");

exit(1);
