#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

use League\CLImate\CLImate;
use LizardsAndPumpkins\Messaging\Queue;
use LizardsAndPumpkins\Util\BaseCliCommand;
use LizardsAndPumpkins\Util\Factory\CommonFactory;
use LizardsAndPumpkins\Util\Factory\MasterFactory;
use LizardsAndPumpkins\Util\Factory\SampleMasterFactory;
use LizardsAndPumpkins\Util\Factory\ProjectFactory;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ReportQueueCount extends BaseCliCommand
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

    public static function bootstrap() : ReportQueueCount
    {
        $factory = new SampleMasterFactory();
        $factory->register(new CommonFactory());
        $factory->register(new ProjectFactory());

        return new self($factory, new CLImate());
    }

    protected function execute(CLImate $climate)
    {
        $tableData = $this->formatTableData(
            $this->factory->getCommandMessageQueue(),
            $this->factory->getEventMessageQueue()
        );
        $climate->table($tableData);
    }

    /**
     * @param Queue $commandQueue
     * @param Queue $eventQueue
     * @return string[]
     */
    private function formatTableData(Queue $commandQueue, Queue $eventQueue) : array
    {
        return [
            [
                'Queue' => 'Command',
                'Count' => sprintf('%10d', $commandQueue->count())
            ],
            [
                'Queue' => 'Event',
                'Count' => sprintf('%10d', $eventQueue->count())
            ],
        ];
    }
}

ReportQueueCount::bootstrap()->run();
