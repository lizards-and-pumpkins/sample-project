#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

use League\CLImate\CLImate;
use LizardsAndPumpkins\Util\BaseCliCommand;

require_once __DIR__ . '/../../../vendor/autoload.php';

class CalculateAverageDomainEventProcessingTime extends BaseCliCommand
{
    public function __construct()
    {
        $this->setCLImate(new CLImate());
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
                'sortBy'    => [
                    'prefix'       => 's',
                    'longPrefix'   => 'sortBy',
                    'description'  => 'Sort by field (handler|count|total|avg)',
                    'defaultValue' => 'avg',
                ],
                'direction' => [
                    'prefix'       => 'd',
                    'longPrefix'   => 'direction',
                    'description'  => 'Sort direction (asc|desc)',
                    'defaultValue' => 'asc',
                ],
                'logfile'   => [
                    'description' => 'Log file',
                    'required'    => true,
                ],
            ]
        );
    }

    protected function execute(CLImate $climate)
    {
        $filePath = $this->getArg('logfile');
        $tableData = $this->sortTableData(
            $this->collectTableDataFromFile($filePath),
            $this->getArg('sortBy'),
            $this->getArg('direction')
        );
        if (! $tableData) {
            $climate->yellow('No data to report');
        } else {
            $climate->table($tableData);
        }
    }

    /**
     * @param array[] $tableData
     * @param string $field
     * @param string $direction
     * @return array[]
     */
    private function sortTableData(array $tableData, string $field, string $direction) : array
    {
        $directionalOperator = $direction === 'asc' ? 1 : -1;
        usort(
            $tableData,
            function (array $rowA, array $rowB) use ($field, $directionalOperator) {
                $valueA = $this->getComparisonValueFromRow($rowA, $field);
                $valueB = $this->getComparisonValueFromRow($rowB, $field);
                $result = $this->threeWayCompare($valueA, $valueB);
                return $result * $directionalOperator;
            }
        );
        return $tableData;
    }

    /**
     * @param mixed $valueA
     * @param mixed $valueB
     * @return int
     */
    private function threeWayCompare($valueA, $valueB) : int
    {
        return $valueA <=> $valueB;
    }

    /**
     * @param mixed[] $row
     * @param string $field
     * @return mixed
     */
    private function getComparisonValueFromRow(array $row, string $field)
    {
        $key = $this->getArrayKeyFromSortByField($field);
        return $row[$key];
    }

    private function getArrayKeyFromSortByField(string $field) : string
    {
        return array_search(
            $field,
            [
                'Handler'     => 'handler',
                'Count'       => 'count',
                'Total Sec'   => 'total',
                'Average Sec' => 'avg',
            ]
        );
    }

    private function collectTableDataFromFile(string $filePath) : array
    {
        $eventHandlerStats = $this->readEventHandlerStatsFromFile($filePath);
        return $this->buildTableDataFromStats($eventHandlerStats);
    }

    /**
     * @param string $filePath
     * @return array[]
     */
    private function readEventHandlerStatsFromFile(string $filePath) : array
    {
        $eventHandlers = [];
        foreach ($this->getDomainEventHandlerRecordsFromFile($filePath) as $record) {
            list($domainEventHandler, $time) = $record;
            $eventHandlers[$domainEventHandler][] = $time;
        }
        return $eventHandlers;
    }

    private function getDomainEventHandlerRecordsFromFile(string $filePath)
    {
        $f = fopen($filePath, 'r');
        $matches = null;
        while (! feof($f)) {
            if (preg_match("/^.{25}\tDomainEventHandler::process (\\S+) (\\S+)/", (string) fgets($f), $matches)) {
                yield array_slice($matches, 1);
            }
        }
        fclose($f);
    }

    /**
     * @param array[] $eventHandlerStats
     * @return array[]
     */
    private function buildTableDataFromStats(array $eventHandlerStats) : array
    {
        return array_map(
            function ($handler) use ($eventHandlerStats) {
                $count = count($eventHandlerStats[$handler]);
                $sum = array_sum($eventHandlerStats[$handler]);
                return $this->getTableRow($handler, $count, $sum);
            },
            array_keys($eventHandlerStats)
        );
    }

    /**
     * @param string $handler
     * @param int $count
     * @param float|int $sum
     * @return mixed[]
     */
    private function getTableRow(string $handler, int $count, $sum) : array
    {
        return [
            'Handler'     => $handler,
            'Count'       => $count,
            'Total Sec'   => sprintf('%11.4F', $sum),
            'Average Sec' => sprintf('%.4F', $sum / $count),
        ];
    }

    protected function beforeExecute(CLImate $climate)
    {
        $this->validateLogFilePath($climate->arguments->get('logfile'));
        $this->validateSortField($climate->arguments->get('sortBy'));
        $this->validateSortDirection($climate->arguments->get('direction'));
    }

    private function validateLogFilePath(string $filePath)
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException(sprintf('Log file not found: "%s"', $filePath));
        }
        if (! is_readable($filePath)) {
            throw new \RuntimeException(sprintf('Log file not readable: "%s"', $filePath));
        }
    }

    private function validateSortField(string $order)
    {
        if (! in_array($order, ['handler', 'count', 'total', 'avg'])) {
            throw new \RuntimeException(sprintf('Invalid order: "%s"', $order));
        }
    }

    private function validateSortDirection(string $direction)
    {
        if (! in_array($direction, ['asc', 'desc'])) {
            throw new \RuntimeException(sprintf('Invalid sort direction: "%s"', $direction));
        }
    }
}

(new CalculateAverageDomainEventProcessingTime())->run();
