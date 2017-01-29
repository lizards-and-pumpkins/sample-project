#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

use League\CLImate\CLImate;
use LizardsAndPumpkins\Context\DataVersion\DataVersion;
use LizardsAndPumpkins\Import\CatalogImport;
use LizardsAndPumpkins\Import\Image\NullProductImageImportCommandFactory;
use LizardsAndPumpkins\Import\Image\UpdatingProductImageImportCommandFactory;
use LizardsAndPumpkins\Logging\LoggingCommandHandlerFactory;
use LizardsAndPumpkins\Logging\LoggingDomainEventHandlerFactory;
use LizardsAndPumpkins\Logging\LoggingQueueFactory;
use LizardsAndPumpkins\ProductDetail\Import\UpdatingProductImportCommandFactory;
use LizardsAndPumpkins\ProductListing\Import\UpdatingProductListingImportCommandFactory;
use LizardsAndPumpkins\Util\BaseCliCommand;
use LizardsAndPumpkins\Util\Factory\CommonFactory;
use LizardsAndPumpkins\Util\Factory\MasterFactory;
use LizardsAndPumpkins\Util\Factory\SampleMasterFactory;
use LizardsAndPumpkins\Util\Factory\DemoProjectFactory;

require_once __DIR__ . '/../../../vendor/autoload.php';

class RunImport extends BaseCliCommand
{
    /**
     * @var SampleMasterFactory
     */
    private $factory;

    private function __construct(MasterFactory $factory, CLImate $CLImate)
    {
        $this->factory = $factory;
        $this->setCLImate($CLImate);
    }

    public static function bootstrap() : RunImport
    {
        $factory = new SampleMasterFactory();
        $commonFactory = new CommonFactory();
        $implementationFactory = new DemoProjectFactory();
        $factory->register($commonFactory);
        $factory->register($implementationFactory);
        $factory->register(new UpdatingProductImportCommandFactory());
        $factory->register(new UpdatingProductListingImportCommandFactory());
        //self::enableDebugLogging($factory, $commonFactory, $implementationFactory);

        return new self($factory, new CLImate());
    }

    private static function enableDebugLogging(
        MasterFactory $factory,
        CommonFactory $commonFactory,
        DemoProjectFactory $implementationFactory
    ) {
        $factory->register(new LoggingDomainEventHandlerFactory($commonFactory));
        $factory->register(new LoggingCommandHandlerFactory($commonFactory));
        $factory->register(new LoggingQueueFactory($implementationFactory));
    }

    /**
     * @param CLImate $climate
     * @return array[]
     */
    protected function getCommandLineArgumentsArray(CLImate $climate) : array
    {
        return array_merge(
            parent::getCommandLineArgumentsArray($climate),
            [
                'clearStorage'  => [
                    'prefix'      => 'c',
                    'longPrefix'  => 'clearStorage',
                    'description' => 'Clear queues and data pool before the import',
                    'noValue'     => true,
                ],
                'processQueues' => [
                    'prefix'      => 'p',
                    'longPrefix'  => 'processQueues',
                    'description' => 'Process queues after the import',
                    'noValue'     => true,
                ],
                'importImages'  => [
                    'prefix'      => 'i',
                    'longPrefix'  => 'importImages',
                    'description' => 'Process images during import',
                    'noValue'     => true,
                ],
                'importFile'    => [
                    'description' => 'Import XML file',
                    'required'    => true,
                ],
            ]
        );
    }

    protected function execute(CLImate $CLImate)
    {
        $this->clearStorageIfRequested();
        $this->enableImageImportIfRequested();
        $this->importFile();
        $this->processQueuesIfRequested();
    }

    private function clearStorageIfRequested()
    {
        if ($this->getArg('clearStorage')) {
            $this->clearStorage();
        }
    }

    private function enableImageImportIfRequested()
    {
        if ($this->getArg('importImages')) {
            $this->factory->register(new UpdatingProductImageImportCommandFactory());
        } else {
            $this->factory->register(new NullProductImageImportCommandFactory());
        }
    }

    private function clearStorage()
    {
        $this->output('Clearing queue and data pool before import...');

        $dataPoolWriter = $this->factory->createDataPoolWriter();
        $dataPoolWriter->clear();
    }

    private function importFile()
    {
        $this->output('Importing...');
        
        $dataVersionString = $this->factory->createDataPoolReader()->getCurrentDataVersion();

        /** @var CatalogImport $import */
        $import = $this->factory->createCatalogImport();
        $import->importFile($this->getArg('importFile'), DataVersion::fromVersionString($dataVersionString));
    }

    private function processQueuesIfRequested()
    {
        if ($this->getArg('processQueues')) {
            $this->processQueues();
        }
    }

    private function processQueues()
    {
        $this->processCommandQueue();
        $this->processDomainEventQueue();
    }

    private function processCommandQueue()
    {
        $this->output('Processing command queue...');
        $commandConsumer = $this->factory->createCommandConsumer();
        $commandConsumer->processAll();
    }

    private function processDomainEventQueue()
    {
        $this->output('Processing domain event queue...');
        $domainEventConsumer = $this->factory->createDomainEventConsumer();
        $domainEventConsumer->processAll();
    }
}

RunImport::bootstrap()->run();
