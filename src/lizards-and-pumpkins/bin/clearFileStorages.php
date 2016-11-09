#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

use League\CLImate\CLImate;
use LizardsAndPumpkins\Util\BaseCliCommand;
use LizardsAndPumpkins\Util\Factory\CommonFactory;
use LizardsAndPumpkins\Util\Factory\MasterFactory;
use LizardsAndPumpkins\Util\Factory\SampleMasterFactory;
use LizardsAndPumpkins\Util\Factory\DemoProjectFactory;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ClearFileStorage extends BaseCliCommand
{
    /**
     * @var MasterFactory|CommonFactory
     */
    private $factory;

    private function __construct(MasterFactory $factory, CLImate $climate)
    {
        $this->factory = $factory;
        $this->setCLImate($climate);
    }

    /**
     * @return ClearFileStorage
     */
    public static function bootstrap()
    {
        $factory = new SampleMasterFactory();
        $factory->register(new CommonFactory());
        $factory->register(new DemoProjectFactory());
        
        return new self($factory, new CLImate());
    }
    
    final protected function execute(CLImate $climate)
    {
        $this->factory->createDataPoolWriter()->clear();
        $this->factory->getCommandMessageQueue()->clear();
        $this->factory->getEventMessageQueue()->clear();

        $this->output('Cleared data pool and queues');
        $this->output(sprintf("Storage dir: %s\n", $this->factory->getFileStorageBasePathConfig()));
    }
}

ClearFileStorage::bootstrap()->run();
