#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

use LizardsAndPumpkins\Logging\LoggingCommandHandlerFactory;
use LizardsAndPumpkins\Logging\LoggingQueueFactory;
use LizardsAndPumpkins\Util\Factory\CatalogMasterFactory;
use LizardsAndPumpkins\Util\Factory\CommonFactory;
use LizardsAndPumpkins\Util\Factory\ProjectFactory;

require_once __DIR__ . '/../../../vendor/autoload.php';

class CommandConsumerWorker
{
    /**
     * @var CatalogMasterFactory
     */
    private $factory;

    private function __construct()
    {
        $this->factory = new CatalogMasterFactory();
        $commonFactory = new CommonFactory();
        $implementationFactory = new ProjectFactory();
        $this->factory->register($commonFactory);
        $this->factory->register($implementationFactory);
        //$this->enableDebugLogging($commonFactory, $implementationFactory);
    }

    private function enableDebugLogging(CommonFactory $commonFactory, ProjectFactory $implementationFactory)
    {
        $this->factory->register(new LoggingCommandHandlerFactory($commonFactory));
        $this->factory->register(new LoggingQueueFactory($implementationFactory));
    }

    public static function run()
    {
        $worker = new self();
        $commandConsumer = $worker->factory->createCommandConsumer();
        $commandConsumer->process();
    }
}

CommandConsumerWorker::run();
