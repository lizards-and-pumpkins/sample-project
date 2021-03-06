<?php

declare(strict_types = 1);

namespace LizardsAndPumpkins\Util\Factory;

use LizardsAndPumpkins\Http\ContentDelivery\PageBuilder\DemoSitePageBuilderDecorator;
use LizardsAndPumpkins\Http\ContentDelivery\PageBuilder\PageBuilder;

class DecoratorFactory implements FactoryWithCallback
{
    use FactoryWithCallbackTrait;

    /**
     * @var PageBuilder
     */
    private $undecoratedPageBuilder;

    public function beforeFactoryRegistrationCallback(MasterFactory $masterFactory)
    {
        if ($masterFactory->hasMethod('createPageBuilder')) {
            $this->undecoratedPageBuilder = $masterFactory->createPageBuilder();
        }
    }

    public function createPageBuilder(): PageBuilder
    {
        return new DemoSitePageBuilderDecorator($this->undecoratedPageBuilder);
    }
}
