<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Context;

use LizardsAndPumpkins\Context\Locale\Locale;
use LizardsAndPumpkins\Context\Website\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Context\DemoProjectContextSource
 */
class DemoProjectContextSourceTest extends TestCase
{
    public function testExpectedContextMatrixIsReturned(): void
    {
        $expectedContextMatrix = [
            [Website::CONTEXT_CODE => 'de', Locale::CONTEXT_CODE => 'de_DE'],
            [Website::CONTEXT_CODE => 'en', Locale::CONTEXT_CODE => 'en_US'],
            [Website::CONTEXT_CODE => 'fr', Locale::CONTEXT_CODE => 'fr_FR'],
        ];

        /** @var ContextBuilder|MockObject $stubContextBuilder */
        $stubContextBuilder = $this->createMock(ContextBuilder::class);
        $stubContextBuilder->expects($this->once())
            ->method('createContextsFromDataSets')
            ->with($expectedContextMatrix);

        (new DemoProjectContextSource($stubContextBuilder))->getAllAvailableContexts();
    }
}
