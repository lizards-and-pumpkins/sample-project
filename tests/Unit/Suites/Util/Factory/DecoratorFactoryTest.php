<?php

declare(strict_types = 1);

namespace LizardsAndPumpkins\Util\Factory;

use LizardsAndPumpkins\Http\ContentDelivery\PageBuilder\DemoSitePageBuilderDecorator;
use LizardsAndPumpkins\Http\ContentDelivery\PageBuilder\PageBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Util\Factory\DecoratorFactory
 * @uses   \LizardsAndPumpkins\Http\ContentDelivery\PageBuilder\DemoSitePageBuilderDecorator
 */
class DecoratorFactoryTest extends TestCase
{
    public function testImplementsFactoryWithCallback()
    {
        $this->assertInstanceOf(FactoryWithCallback::class, new DecoratorFactory());
    }

    public function testGetsTheUndecoratedPageBuilderFromMasterFactoryBeforeRegistration()
    {
        $mockMasterFactory = $this->getMockBuilder(MasterFactory::class)
            ->setMethods(array_merge(get_class_methods(MasterFactory::class), ['createPageBuilder']))
            ->getMock();

        $mockMasterFactory->method('hasMethod')->with('createPageBuilder')->willReturn(true);
        $mockMasterFactory->expects($this->once())->method('createPageBuilder')
            ->willReturn($this->createMock(PageBuilder::class));
        
        (new DecoratorFactory())->beforeFactoryRegistrationCallback($mockMasterFactory);
    }

    public function testDoesNotGetTheUndecoratedPageBuilderFromMasterFactoryIfNoPageBuilderFactory()
    {
        $mockMasterFactory = $this->getMockBuilder(MasterFactory::class)
            ->setMethods(array_merge(get_class_methods(MasterFactory::class), ['createPageBuilder']))
            ->getMock();

        $mockMasterFactory->method('hasMethod')->with('createPageBuilder')->willReturn(false);
        $mockMasterFactory->expects($this->never())->method('createPageBuilder');
        
        (new DecoratorFactory())->beforeFactoryRegistrationCallback($mockMasterFactory);
    }

    public function testReturnsADemoSitePageBuilderDecorator()
    {
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
