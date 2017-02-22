#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

use LizardsAndPumpkins\Import\Image\UpdatingProductImageImportCommandFactory;
use LizardsAndPumpkins\Logging\LoggingDomainEventHandlerFactory;
use LizardsAndPumpkins\Logging\LoggingQueueFactory;
use LizardsAndPumpkins\ProductDetail\Import\UpdatingProductImportCommandFactory;
use LizardsAndPumpkins\ProductListing\Import\UpdatingProductListingImportCommandFactory;
use LizardsAndPumpkins\Util\Factory\CommonFactory;
use LizardsAndPumpkins\Util\Factory\SampleMasterFactory;
use LizardsAndPumpkins\Util\Factory\ProjectFactory;

require_once __DIR__ . '/../../../vendor/autoload.php';

class EventConsumerWorker
{
    /**
     * @var SampleMasterFactory
     */
    private $factory;

    private function __construct()
    {
        $this->factory = new SampleMasterFactory();
        $commonFactory = new CommonFactory();
        $implementationFactory = new ProjectFactory();
        $this->factory->register($commonFactory);
        $this->factory->register($implementationFactory);
        $this->factory->register(new UpdatingProductImportCommandFactory());
        $this->factory->register(new UpdatingProductImageImportCommandFactory());
        $this->factory->register(new UpdatingProductListingImportCommandFactory());
        //$this->enableDebugLogging($commonFactory, $implementationFactory);
    }

    private function enableDebugLogging(CommonFactory $commonFactory, ProjectFactory $implementationFactory)
    {
        $this->factory->register(new LoggingDomainEventHandlerFactory($commonFactory));
        $this->factory->register(new LoggingQueueFactory($implementationFactory));
    }

    public static function run()
    {
        $worker = new self();
        $eventConsumer = $worker->factory->createDomainEventConsumer();
        $eventConsumer->process();
    }
}

EventConsumerWorker::run();
