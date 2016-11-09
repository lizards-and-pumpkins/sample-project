<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Context;

use LizardsAndPumpkins\Context\Locale\Locale;
use LizardsAndPumpkins\Context\Website\Website;

class DemoProjectContextSource extends ContextSource
{
    /**
     * @return array[]
     */
    final protected function getContextMatrix() : array
    {
        return [
            [Website::CONTEXT_CODE => 'de', Locale::CONTEXT_CODE => 'de_DE'],
            [Website::CONTEXT_CODE => 'en', Locale::CONTEXT_CODE => 'en_US'],
            [Website::CONTEXT_CODE => 'fr', Locale::CONTEXT_CODE => 'fr_FR'],
        ];
    }
}
