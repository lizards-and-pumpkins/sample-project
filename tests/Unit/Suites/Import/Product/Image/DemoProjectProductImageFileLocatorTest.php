<?php

namespace LizardsAndPumpkins\Import\Product\Image;

use LizardsAndPumpkins\Context\Context;
use LizardsAndPumpkins\Context\Locale\Locale;
use LizardsAndPumpkins\Context\Website\Website;
use LizardsAndPumpkins\Import\FileStorage\StorageAgnosticFileUri;
use LizardsAndPumpkins\Import\ImageStorage\Exception\InvalidImageFileNameException;
use LizardsAndPumpkins\Import\ImageStorage\Exception\InvalidImageVariantCodeException;
use LizardsAndPumpkins\Import\ImageStorage\Image;
use LizardsAndPumpkins\Import\ImageStorage\ImageStorage;
use LizardsAndPumpkins\Import\Product\View\ProductImageFileLocator;

/**
 * @covers \LizardsAndPumpkins\Import\Product\Image\DemoProjectProductImageFileLocator
 */
class DemoProjectProductImageFileLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DemoProjectProductImageFileLocator
     */
    private $productImageFileLocator;

    /**
     * @var ImageStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubImageStorage;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubContext;

    /**
     * @param string $imageVariantCode
     * @return Image|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createStubPlaceholderImage($imageVariantCode)
    {
        $placeholderIdentifier = $this->stringStartsWith('product/placeholder/' . $imageVariantCode . '/');
        $stubPlaceholderImage = $this->getMock(Image::class);
        $this->stubImageStorage
            ->method('getFileReference')
            ->with($placeholderIdentifier)
            ->willReturn($stubPlaceholderImage);
        return $stubPlaceholderImage;
    }

    protected function setUp()
    {
        $this->stubContext = $this->getMock(Context::class);
        $this->stubContext->method('getValue')->willReturnMap([
            [Locale::CONTEXT_CODE, 'xx_XX'],
            [Website::CONTEXT_CODE, 'web123'],
        ]);
        $this->stubImageStorage = $this->getMock(ImageStorage::class);

        $this->productImageFileLocator = new DemoProjectProductImageFileLocator($this->stubImageStorage);
    }

    public function testItImplementsTheProductImageInterface()
    {
        $this->assertInstanceOf(ProductImageFileLocator::class, $this->productImageFileLocator);
        $this->assertInstanceOf(DemoProjectProductImageFileLocator::class, $this->productImageFileLocator);
    }

    /**
     * @param mixed $invalidImageVariantCode
     * @param string $invalidType
     * @dataProvider invalidImageVariantCodeProvider
     */
    public function testItThrowsAnExceptionIfImageVariantCodeNotValid($invalidImageVariantCode, $invalidType)
    {
        $msg = 'The image variant code must be one of original, large, medium, small, search-autosuggestion, got "%s"';
        $this->expectException(InvalidImageVariantCodeException::class);
        $this->expectExceptionMessage(sprintf($msg, $invalidType));

        $imageFileName = 'test.jpg';
        $this->productImageFileLocator->get($imageFileName, $invalidImageVariantCode, $this->stubContext);
    }

    /**
     * @return array[]
     */
    public function invalidImageVariantCodeProvider()
    {
        return [
            ['invalid', 'invalid'],
            [123, 'integer'],
            [$this, get_class($this)],
        ];
    }

    public function testItThrowsAnExceptionIfTheFileNameIsNotAString()
    {
        $this->expectException(InvalidImageFileNameException::class);
        $this->expectExceptionMessage(sprintf('The image file name must be a string, got "integer"'));

        $invalidImageFileName = 123;
        $variantCode = DemoProjectProductImageFileLocator::SMALL;
        $this->productImageFileLocator->get($invalidImageFileName, $variantCode, $this->stubContext);
    }

    public function testItReturnsAPlaceholderIfTheImageFileNameIsEmpty()
    {
        $emptyImageFileName = ' ';
        $variantCode = DemoProjectProductImageFileLocator::SMALL;
        $this->stubImageStorage->method('contains')->willReturn(true);
        $stubPlaceholderImage = $this->createStubPlaceholderImage($variantCode);
        
        $result = $this->productImageFileLocator->get($emptyImageFileName, $variantCode, $this->stubContext);
        $this->assertSame($stubPlaceholderImage, $result);
    }

    /**
     * @param string $imageVariantCode
     * @dataProvider validImageVariantCodeProvider
     */
    public function testItReturnsAProductImageFileInstanceForValidVariantCodes($imageVariantCode)
    {
        $imageIdentifier = sprintf('product/%s/test.jpg', $imageVariantCode);
        $stubImage = $this->getMock(Image::class);

        $this->stubImageStorage->expects($this->once())
            ->method('getFileReference')
            ->with($this->isInstanceOf(StorageAgnosticFileUri::class))
            ->willReturn($stubImage);

        $result = $this->productImageFileLocator->get($imageIdentifier, $imageVariantCode, $this->stubContext);
        $this->assertSame($stubImage, $result);
    }

    /**
     * @return array[]
     */
    public function validImageVariantCodeProvider()
    {
        return [
            [DemoProjectProductImageFileLocator::SMALL],
            [DemoProjectProductImageFileLocator::MEDIUM],
            [DemoProjectProductImageFileLocator::LARGE],
            [DemoProjectProductImageFileLocator::ORIGINAL],
            [DemoProjectProductImageFileLocator::SEARCH_AUTOSUGGESTION],
        ];
    }

    public function testItReturnsAllValidImageVariantCodes()
    {
        $validImageVariantCodes = [
            DemoProjectProductImageFileLocator::SMALL,
            DemoProjectProductImageFileLocator::MEDIUM,
            DemoProjectProductImageFileLocator::LARGE,
            DemoProjectProductImageFileLocator::ORIGINAL,
            DemoProjectProductImageFileLocator::SEARCH_AUTOSUGGESTION,
        ];

        $result = $this->productImageFileLocator->getVariantCodes();
        
        sort($result);
        sort($validImageVariantCodes);
        
        $this->assertSame($validImageVariantCodes, $result);
    }
}
