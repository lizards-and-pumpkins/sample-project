#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

use League\CLImate\CLImate;
use LizardsAndPumpkins\Import\RootTemplate\Import\TemplateProjectorLocator;
use LizardsAndPumpkins\Import\RootTemplate\TemplateWasUpdatedDomainEvent;
use LizardsAndPumpkins\Logging\LoggingCommandHandlerFactory;
use LizardsAndPumpkins\Logging\LoggingDomainEventHandlerFactory;
use LizardsAndPumpkins\Logging\LoggingQueueFactory;
use LizardsAndPumpkins\Util\BaseCliCommand;
use LizardsAndPumpkins\Util\Factory\CommonFactory;
use LizardsAndPumpkins\Util\Factory\MasterFactory;
use LizardsAndPumpkins\Util\Factory\SampleMasterFactory;
use LizardsAndPumpkins\Util\Factory\DemoProjectFactory;

require_once __DIR__ . '/../../../vendor/autoload.php';

class TriggerTemplateUpdate extends BaseCliCommand
{
    /**
     * @var MasterFactory
     */
    private $factory;

    private function __construct(MasterFactory $factory, CLImate $CLImate)
    {
        $this->factory = $factory;
        $this->setCLImate($CLImate);
    }

    public static function bootstrap() : TriggerTemplateUpdate
    {
        $factory = new SampleMasterFactory();
        $commonFactory = new CommonFactory();
        $implementationFactory = new DemoProjectFactory();
        $factory->register($commonFactory);
        $factory->register($implementationFactory);
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
                'processQueues' => [
                    'prefix'      => 'p',
                    'longPrefix'  => 'processQueues',
                    'description' => 'Process queues',
                    'noValue'     => true,
                ],
                'list'          => [
                    'prefix'      => 'l',
                    'longPrefix'  => 'list',
                    'description' => 'List available template IDs',
                    'noValue'     => true,
                ],
                'templateId'    => [
                    'description' => 'Template ID',
                    'required'    => false,
                ],
            ]
        );
    }

    protected function execute(CLImate $CLImate)
    {
        if ($this->isTemplateIdListRequested()) {
            $this->outputTemplateIdList();
            return;
        }
        $this->addDomainEvent();
        $this->processQueuesIfRequested();
    }

    private function addDomainEvent()
    {
        $templateId = $this->getTemplateIdToProject();
        $projectionSourceData = '';

        $this->factory->getEventQueue()->add(new TemplateWasUpdatedDomainEvent($templateId, $projectionSourceData));
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
        $commandConsumer->process();
    }

    private function processDomainEventQueue()
    {
        $this->output('Processing domain event queue...');
        $domainEventConsumer = $this->factory->createDomainEventConsumer();
        $domainEventConsumer->process();
    }

    private function getTemplateIdToProject() : string
    {
        $templateId = $this->getArg('templateId');
        if (! in_array($templateId, $this->getValidTemplateIds())) {
            $message = $this->getInvalidTemplateIdMessage($templateId);
            throw new \InvalidArgumentException($message);
        }
        return $templateId;
    }

    private function getInvalidTemplateIdMessage(string $templateId) : string
    {
        return sprintf(
            'Invalid template ID "%s". Valid template IDs are: %s',
            $templateId,
            implode(', ', $this->getValidTemplateIds())
        );
    }

    /**
     * @return string[]
     */
    private function getValidTemplateIds() : array
    {
        /** @var TemplateProjectorLocator $templateProjectorLocator */
        $templateProjectorLocator = $this->factory->createTemplateProjectorLocator();
        return $templateProjectorLocator->getRegisteredProjectorCodes();
    }

    protected function isTemplateIdListRequested() : bool
    {
        return (bool) $this->getArg('list');
    }

    protected function outputTemplateIdList()
    {
        $this->output('Available template IDs:');
        $this->output(implode(PHP_EOL, $this->getValidTemplateIds()));
    }
}

TriggerTemplateUpdate::bootstrap()->run();
