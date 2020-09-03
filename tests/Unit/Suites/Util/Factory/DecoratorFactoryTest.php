<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Util\Factory;

use LizardsAndPumpkins\Core\Factory\FactoryWithCallback;
use LizardsAndPumpkins\Core\Factory\MasterFactory;
use LizardsAndPumpkins\Http\ContentDelivery\PageBuilder\DemoSitePageBuilderDecorator;
use LizardsAndPumpkins\Http\ContentDelivery\PageBuilder\PageBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Util\Factory\DecoratorFactory
 * @uses   \LizardsAndPumpkins\Http\ContentDelivery\PageBuilder\DemoSitePageBuilderDecorator
 */
class DecoratorFactoryTest extends TestCase
{
    public function testImplementsFactoryWithCallback(): void
    {
        $this->assertInstanceOf(FactoryWithCallback::class, new DecoratorFactory());
    }

    public function testGetsTheUndecoratedPageBuilderFromMasterFactoryBeforeRegistration(): void
    {
        /** @var MasterFactory|MockObject $mockMasterFactory */
        $mockMasterFactory = $this->getMockBuilder(MasterFactory::class)
            ->setMethods(array_merge(get_class_methods(MasterFactory::class), ['createPageBuilder']))
            ->getMock();

        $mockMasterFactory->method('hasMethod')->with('createPageBuilder')->willReturn(true);
        $mockMasterFactory->expects($this->once())->method('createPageBuilder')
            ->willReturn($this->createMock(PageBuilder::class));

        (new DecoratorFactory())->beforeFactoryRegistrationCallback($mockMasterFactory);
    }

    public function testDoesNotGetTheUndecoratedPageBuilderFromMasterFactoryIfNoPageBuilderFactory(): void
    {
        /** @var MasterFactory|MockObject $mockMasterFactory */
        $mockMasterFactory = $this->getMockBuilder(MasterFactory::class)
            ->setMethods(array_merge(get_class_methods(MasterFactory::class), ['createPageBuilder']))
            ->getMock();

        $mockMasterFactory->method('hasMethod')->with('createPageBuilder')->willReturn(false);
        $mockMasterFactory->expects($this->never())->method('createPageBuilder');

        (new DecoratorFactory())->beforeFactoryRegistrationCallback($mockMasterFactory);
    }

    public function testReturnsADemoSitePageBuilderDecorator(): void
    {
        /** @var MasterFactory|MockObject $mockMasterFactory */
        $mockMasterFactory = $this->getMockBuilder(MasterFactory::class)
            ->setMethods(array_merge(get_class_methods(MasterFactory::class), ['createPageBuilder']))
            ->getMock();

        $mockMasterFactory->method('hasMethod')->with('createPageBuilder')->willReturn(true);
        $mockMasterFactory->expects($this->once())->method('createPageBuilder')
            ->willReturn($this->createMock(PageBuilder::class));

        $decoratorFactory = new DecoratorFactory();
        $decoratorFactory->beforeFactoryRegistrationCallback($mockMasterFactory);
        $result = $decoratorFactory->createPageBuilder();

        $this->assertInstanceOf(DemoSitePageBuilderDecorator::class, $result);
    }
}
