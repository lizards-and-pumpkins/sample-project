#!/usr/bin/env php
<?php

namespace LizardsAndPumpkins;

use LizardsAndPumpkins\Logging\LoggingCommandHandlerFactory;
use LizardsAndPumpkins\Logging\LoggingQueueFactory;
use LizardsAndPumpkins\Util\Factory\CommonFactory;
use LizardsAndPumpkins\Util\Factory\SampleMasterFactory;
use LizardsAndPumpkins\Util\Factory\DemoProjectFactory;

require_once __DIR__ . '/../../../vendor/autoload.php';

class CommandConsumerWorker
{
    /**
     * @var SampleMasterFactory
     */
    private $factory;

    private function __construct()
    {
        $this->factory = new SampleMasterFactory();
        $commonFactory = new CommonFactory();
        $implementationFactory = new DemoProjectFactory();
        $this->factory->register($commonFactory);
        $this->factory->register($implementationFactory);
        //$this->enableDebugLogging($commonFactory, $implementationFactory);
    }

    private function enableDebugLogging(CommonFactory $commonFactory, DemoProjectFactory $implementationFactory)
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
