<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\Image;

use LizardsAndPumpkins\Context\Context;
use LizardsAndPumpkins\Context\Locale\Locale;
use LizardsAndPumpkins\Context\Website\Website;
use LizardsAndPumpkins\Import\FileStorage\StorageAgnosticFileUri;
use LizardsAndPumpkins\Import\ImageStorage\Exception\InvalidImageVariantCodeException;
use LizardsAndPumpkins\Import\ImageStorage\Image;
use LizardsAndPumpkins\Import\ImageStorage\ImageStorage;
use LizardsAndPumpkins\Import\Product\View\ProductImageFileLocator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Import\Product\Image\DemoProjectProductImageFileLocator
 */
class DemoProjectProductImageFileLocatorTest extends TestCase
{
    /**
     * @var DemoProjectProductImageFileLocator
     */
    private $productImageFileLocator;

    /**
     * @var ImageStorage
     */
    private $stubImageStorage;

    /**
     * @var Context
     */
    private $stubContext;

    private function createStubPlaceholderImage(string $imageVariantCode): Image
    {
        $placeholderIdentifier = $this->stringStartsWith('product/placeholder/' . $imageVariantCode . '/');
        $stubPlaceholderImage = $this->createMock(Image::class);
        $this->stubImageStorage
            ->method('getFileReference')
            ->with($placeholderIdentifier)
            ->willReturn($stubPlaceholderImage);

        return $stubPlaceholderImage;
    }

    final protected function setUp(): void
    {
        $this->stubContext = $this->createMock(Context::class);
        $this->stubContext->method('getValue')->willReturnMap([
            [Locale::CONTEXT_CODE, 'xx_XX'],
            [Website::CONTEXT_CODE, 'web123'],
        ]);
        $this->stubImageStorage = $this->createMock(ImageStorage::class);

        $this->productImageFileLocator = new DemoProjectProductImageFileLocator($this->stubImageStorage);
    }

    public function testItImplementsTheProductImageInterface(): void
    {
        $this->assertInstanceOf(ProductImageFileLocator::class, $this->productImageFileLocator);
        $this->assertInstanceOf(DemoProjectProductImageFileLocator::class, $this->productImageFileLocator);
    }

    public function testItThrowsAnExceptionIfImageVariantCodeIsNotAString(): void
    {
        $this->expectException(\TypeError::class);

        $imageFileName = 'test.jpg';
        $invalidImageVariantCode = 123;

        $this->productImageFileLocator->get($imageFileName, $invalidImageVariantCode, $this->stubContext);
    }

    public function testItThrowsAnExceptionIfImageVariantCodeNotValid(): void
    {
        $imageFileName = 'test.jpg';
        $invalidImageVariantCode = 'invalid';

        $msg = 'The image variant code must be one of original, large, medium, small, search-autosuggestion, got "%s"';
        $this->expectException(InvalidImageVariantCodeException::class);
        $this->expectExceptionMessage(sprintf($msg, $invalidImageVariantCode));

        $this->productImageFileLocator->get($imageFileName, $invalidImageVariantCode, $this->stubContext);
    }

    public function testItThrowsAnExceptionIfTheFileNameIsNotAString(): void
    {
        $this->expectException(\TypeError::class);

        $invalidImageFileName = 123;
        $variantCode = DemoProjectProductImageFileLocator::SMALL;

        $this->productImageFileLocator->get($invalidImageFileName, $variantCode, $this->stubContext);
    }

    public function testItReturnsAPlaceholderIfTheImageFileNameIsEmpty(): void
    {
        $emptyImageFileName = ' ';
        $variantCode = DemoProjectProductImageFileLocator::SMALL;
        $this->stubImageStorage->method('contains')->willReturn(true);
        $stubPlaceholderImage = $this->createStubPlaceholderImage($variantCode);

        $result = $this->productImageFileLocator->get($emptyImageFileName, $variantCode, $this->stubContext);
        $this->assertSame($stubPlaceholderImage, $result);
    }

    /**
     * @dataProvider validImageVariantCodeProvider
     * @param string $imageVariantCode
     */
    public function testItReturnsAProductImageFileInstanceForValidVariantCodes(string $imageVariantCode): void
    {
        $imageIdentifier = sprintf('product/%s/test.jpg', $imageVariantCode);
        $stubImage = $this->createMock(Image::class);

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
    public function validImageVariantCodeProvider(): array
    {
        return [
            [DemoProjectProductImageFileLocator::SMALL],
            [DemoProjectProductImageFileLocator::MEDIUM],
            [DemoProjectProductImageFileLocator::LARGE],
            [DemoProjectProductImageFileLocator::ORIGINAL],
            [DemoProjectProductImageFileLocator::SEARCH_AUTOSUGGESTION],
        ];
    }

    public function testItReturnsAllValidImageVariantCodes(): void
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
