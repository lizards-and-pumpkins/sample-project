#!/usr/bin/env php
<?php

declare(strict_types = 1);

namespace LizardsAndPumpkins;

use League\CLImate\CLImate;
use LizardsAndPumpkins\Messaging\Consumer\ShutdownWorkerDirective;
use LizardsAndPumpkins\Util\BaseCliCommand;
use LizardsAndPumpkins\Util\Factory\CommonFactory;
use LizardsAndPumpkins\Util\Factory\MasterFactory;
use LizardsAndPumpkins\Util\Factory\SampleMasterFactory;
use LizardsAndPumpkins\Util\Factory\DemoProjectFactory;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ShutdownConsumer extends BaseCliCommand
{
    /**
     * @var SampleMasterFactory
     */
    private $factory;

    private function __construct(MasterFactory $factory, CLImate $climate)
    {
        $this->factory = $factory;
        $this->setCLImate($climate);
    }

    public static function bootstrap(): ShutdownConsumer
    {
        $factory = new SampleMasterFactory();
        $factory->register(new CommonFactory());
        $factory->register(new DemoProjectFactory());

        return new self($factory, new CLImate());
    }

    /**
     * @param CLImate $climate
     * @return array[]
     */
    protected function getCommandLineArgumentsArray(CLImate $climate): array
    {
        return array_merge(
            parent::getCommandLineArgumentsArray($climate),
            [
                'quiet' => [
                    'prefix' => 'q',
                    'longPrefix' => 'quiet',
                    'description' => 'No output',
                    'noValue' => true
                ],
                'type' => [
                    'description' => '"command" or "event"',
                    'required'    => true,
                ],
                'pid'  => [
                    'description'  => 'numeric PID',
                    'required'     => true,
                ],
            ]
        );
    }

    protected function execute(CLImate $climate)
    {
        $queue = $this->selectQueue();
        $queue->add($this->createShutdownDirective());
        $this->displayMessage();
    }

    private function type() : string
    {
        $type = $this->getArg('type');
        if ('command' !== $type && 'event' !== $type) {
            throw new \InvalidArgumentException('Type must be "command" or "event"');
        }

        return $type;
    }

    private function pid() : int
    {
        return (int) $this->getArg('pid');
    }

    /**
     * @return Messaging\Command\CommandQueue|Messaging\Event\DomainEventQueue
     */
    private function selectQueue()
    {
        return $this->type() === 'command' ? $this->factory->getCommandQueue() : $this->factory->getEventQueue();
    }

    private function createShutdownDirective(): ShutdownWorkerDirective
    {
        return new ShutdownWorkerDirective((string) $this->pid());
    }

    private function displayMessage()
    {
        if (!$this->getArg('quiet')) {
            $format = 'Shutdown directive for %s consumer with pid "%s" added';
            $this->output(sprintf($format, $this->type(), $this->pid()));
        }
    }
}

ShutdownConsumer::bootstrap()->run();
