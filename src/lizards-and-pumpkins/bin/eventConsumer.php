#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

const REPLACEMENT_COMMAND = 'vendor/bin/lp consume:events';

printf("NOTE:\nThis script is deprecated! Instead, use the command from the catalog repository:\n");
printf("%s\n\n", REPLACEMENT_COMMAND);

for ($i = 0; $i < 3; $i++) {
    echo '.';
    sleep(1);
}
echo "\n";

passthru(
    __DIR__ . '/../../../' .
    REPLACEMENT_COMMAND . ' ' .
    implode(' ', array_map('escapeshellarg', array_slice($argv, 1))),
    $exitStatusCode
);

exit($exitStatusCode);
