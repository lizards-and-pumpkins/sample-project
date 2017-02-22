<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Util\Factory;

use LizardsAndPumpkins\Context\Context;
use LizardsAndPumpkins\Context\DemoProjectContextSource;
use LizardsAndPumpkins\Context\Website\WebsiteToCountryMap;
use LizardsAndPumpkins\DataPool\KeyValueStore\File\FileKeyValueStore;
use LizardsAndPumpkins\DataPool\SearchEngine\FacetFilterRequestField;
use LizardsAndPumpkins\DataPool\SearchEngine\Filesystem\FileSearchEngine;
use LizardsAndPumpkins\DataPool\SearchEngine\Query\SortBy;
use LizardsAndPumpkins\DataPool\UrlKeyStore\FileUrlKeyStore;
use LizardsAndPumpkins\Import\ImageStorage\ImageProcessing\ImageProcessingStrategySequence;
use LizardsAndPumpkins\Import\ImageStorage\ImageProcessing\ImageProcessor;
use LizardsAndPumpkins\Import\ImageStorage\ImageProcessing\ImageProcessorCollection;
use LizardsAndPumpkins\Import\ImageStorage\ImageStorage;
use LizardsAndPumpkins\Import\Product\Image\DemoProjectProductImageFileLocator;
use LizardsAndPumpkins\Import\Product\View\ProductViewLocator;
use LizardsAndPumpkins\Import\Tax\TaxableCountries;
use LizardsAndPumpkins\Import\Tax\TaxServiceLocator;
use LizardsAndPumpkins\Import\TemplateRendering\ThemeLocator;
use LizardsAndPumpkins\Logging\Writer\CompositeLogMessageWriter;
use LizardsAndPumpkins\Logging\WritingLoggerDecorator;
use LizardsAndPumpkins\Messaging\Command\CommandQueue;
use LizardsAndPumpkins\Messaging\Event\DomainEventQueue;
use LizardsAndPumpkins\ProductListing\ContentDelivery\ProductsPerPage;
use LizardsAndPumpkins\ProductListing\Import\DemoProjectProductListingTitleSnippetRenderer;
use LizardsAndPumpkins\ProductSearch\ContentDelivery\SearchFieldToRequestParamMap;
use LizardsAndPumpkins\UnitTestFactory;
use LizardsAndPumpkins\Util\FileSystem\LocalFilesystemStorageReader;
use LizardsAndPumpkins\Util\FileSystem\LocalFilesystemStorageWriter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\Util\Factory\ProjectFactory
 * @uses   \LizardsAndPumpkins\Context\Country\DemoProjectCountryContextPartBuilder
 * @uses   \LizardsAndPumpkins\Context\Locale\DemoProjectLocaleContextPartBuilder
 * @uses   \LizardsAndPumpkins\Context\Website\DemoProjectWebsiteContextPartBuilder
 * @uses   \LizardsAndPumpkins\Context\Website\RequestToWebsiteMap
 * @uses   \LizardsAndPumpkins\Import\Product\Image\DemoProjectProductImageFileLocator
 * @uses   \LizardsAndPumpkins\Import\Product\View\DemoProjectProductViewLocator
 * @uses   \LizardsAndPumpkins\Import\Tax\DemoProjectTaxableCountries
 * @uses   \LizardsAndPumpkins\ProductListing\Import\DemoProjectProductListingTitleSnippetRenderer
 */
class ProjectFactoryTest extends TestCase
{
    /**
     * @var ProjectFactory
     */
    private $factory;

    /**
     * @param FacetFilterRequestField[] $facetFilterFields
     * @return string[]
     */
    private function getFacetCodes(FacetFilterRequestField ...$facetFilterFields) : array
    {
        return array_map(function (FacetFilterRequestField $field) {
            return (string) $field->getAttributeCode();
        }, $facetFilterFields);
    }

    /**
     * @param mixed $newPath
     * @return mixed
     */
    private function changeFileLogPathInEnvironmentConfig($newPath)
    {
        $oldState = null;

        if (isset($_SERVER['LP_LOG_FILE_PATH'])) {
            $oldState = $_SERVER['LP_LOG_FILE_PATH'];
            unset($_SERVER['LP_LOG_FILE_PATH']);
        }

        if (null !== $newPath) {
            $_SERVER['LP_LOG_FILE_PATH'] = $newPath;
        }

        return $oldState;
    }

    protected function setUp()
    {
        $masterFactory = new CatalogMasterFactory();
        $masterFactory->register(new CommonFactory());
        $masterFactory->register(new UnitTestFactory($this));
        $this->factory = new ProjectFactory();
        $masterFactory->register($this->factory);
    }

    protected function tearDown()
    {
        $keyValueStoragePath = sys_get_temp_dir() . '/lizards-and-pumpkins/key-value-store';
        if (file_exists($keyValueStoragePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($keyValueStoragePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $path) {
                $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
            }
            rmdir($keyValueStoragePath);
        }
    }

    public function testCRedisKeyValueStoreIsReturned()
    {
        $this->assertInstanceOf(FileKeyValueStore::class, $this->factory->createKeyValueStore());
    }

    public function testSolrSearchEngineIsReturned()
    {
        $this->assertInstanceOf(FileSearchEngine::class, $this->factory->createSearchEngine());
    }

    public function testInDomainEventQueueIsReturned()
    {
        $this->assertInstanceOf(DomainEventQueue::class, $this->factory->getEventQueue());
    }

    public function testCommandQueueIsReturned()
    {
        $this->assertInstanceOf(CommandQueue::class, $this->factory->getCommandQueue());
    }

    public function testWritingLoggerIsReturned()
    {
        $this->assertInstanceOf(WritingLoggerDecorator::class, $this->factory->createLogger());
    }

    public function testLogMessageWriterIsReturned()
    {
        $this->assertInstanceOf(CompositeLogMessageWriter::class, $this->factory->createLogMessageWriter());
    }

    public function testArrayOfSearchableAttributeCodesIsReturned()
    {
        $result = $this->factory->getSearchableAttributeCodes();

        $this->assertInternalType('array', $result);
        $this->assertContainsOnly('string', $result);
    }

    /**
     * @dataProvider facetFieldsToIncludeInResultProvider
     */
    public function testItReturnsAListOfFacetFilterRequestFieldsForTheProductListings(string $fieldName)
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $stubContext */
        $stubContext = $this->createMock(Context::class);
        $stubContext->method('getValue')->willReturn('DE');
        $fieldCodes = $this->getFacetCodes(...$this->factory->getProductListingFacetFilterRequestFields($stubContext));
        $this->assertContains($fieldName, $fieldCodes);
    }

    public function testItInjectsThePriceAfterTheBrandFacetForProductListings()
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $stubContext */
        $stubContext = $this->createMock(Context::class);
        $stubContext->method('getValue')->willReturn('DE');
        $fieldCodes = $this->getFacetCodes(...$this->factory->getProductListingFacetFilterRequestFields($stubContext));
        $brandPosition = array_search('brand', $fieldCodes, true);
        $this->assertEquals('price_incl_tax_de', $fieldCodes[$brandPosition + 1]);
    }

    /**
     * @dataProvider facetFieldsToIncludeInResultProvider
     */
    public function testItReturnsAListOfFacetFilterRequestFieldsForTheSearchResults(string $fieldName)
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $stubContext */
        $stubContext = $this->createMock(Context::class);
        $stubContext->method('getValue')->willReturn('DE');
        $fieldCodes = $this->getFacetCodes(...$this->factory->getProductSearchFacetFilterRequestFields($stubContext));
        $this->assertContains($fieldName, $fieldCodes);
    }

    public function testItInjectsThePriceAfterTheBrandFacetForSearchListings()
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $stubContext */
        $stubContext = $this->createMock(Context::class);
        $stubContext->method('getValue')->willReturn('DE');
        $fieldCodes = $this->getFacetCodes(...$this->factory->getProductSearchFacetFilterRequestFields($stubContext));
        $brandPosition = array_search('brand', $fieldCodes, true);
        $this->assertEquals('price_incl_tax_de', $fieldCodes[$brandPosition + 1]);
    }

    /**
     * @dataProvider facetFieldsToIndexProvider
     */
    public function testItReturnsAListOfFacetFilterCodesForSearchDocuments(string $fieldName)
    {
        $this->assertContains($fieldName, $this->factory->getFacetFilterRequestFieldCodesForSearchDocuments());
    }

    /**
     * @return array[]
     */
    public function facetFieldsToIncludeInResultProvider() : array
    {
        return array_merge($this->facetFieldsToIndexProvider(), [['price_incl_tax_de']]);
    }

    /**
     * @return array[]
     */
    public function facetFieldsToIndexProvider() : array
    {
        return [
            ['gender'],
            ['product_group'],
            ['style'],
            ['brand'],
            ['series'],
            ['size'],
            ['color'],
        ];
    }

    public function testArrayOfAdditionalAttributeCodesForSearchEngineIsReturned()
    {
        $result = $this->factory->getSortableAttributeCodes();

        $this->assertInternalType('array', $result);
        $this->assertContainsOnly('string', $result);
    }

    public function testImageProcessorCollectionIsReturned()
    {
        $this->assertInstanceOf(ImageProcessorCollection::class, $this->factory->createImageProcessorCollection());
    }

    public function testEnlargedImageProcessorIsReturned()
    {
        $this->assertInstanceOf(ImageProcessor::class, $this->factory->createOriginalImageProcessor());
    }

    public function testFileStorageReaderIsReturned()
    {
        $this->assertInstanceOf(LocalFilesystemStorageReader::class, $this->factory->createFileStorageReader());
    }

    public function testFileStorageWriterIsReturned()
    {
        $this->assertInstanceOf(LocalFilesystemStorageWriter::class, $this->factory->createFileStorageWriter());
    }

    public function testEnlargedImageProcessingStrategySequenceIsReturned()
    {
        $this->assertInstanceOf(
            ImageProcessingStrategySequence::class,
            $this->factory->createOriginalImageProcessingStrategySequence()
        );
    }

    public function testProductDetailsPageImageProcessorIsReturned()
    {
        $this->assertInstanceOf(ImageProcessor::class, $this->factory->createProductDetailsPageImageProcessor());
    }

    public function testProductDetailsPageImageProcessingStrategySequenceIsReturned()
    {
        $this->assertInstanceOf(
            ImageProcessingStrategySequence::class,
            $this->factory->createProductDetailsPageImageProcessingStrategySequence()
        );
    }

    public function testProductListingImageProcessorIsReturned()
    {
        $this->assertInstanceOf(ImageProcessor::class, $this->factory->createProductListingImageProcessor());
    }

    public function testProductListingImageProcessingStrategySequenceIsReturned()
    {
        $this->assertInstanceOf(
            ImageProcessingStrategySequence::class,
            $this->factory->createProductListingImageProcessingStrategySequence()
        );
    }

    public function testGalleyThumbnailImageProcessorIsReturned()
    {
        $this->assertInstanceOf(ImageProcessor::class, $this->factory->createGalleyThumbnailImageProcessor());
    }

    public function testGalleyThumbnailImageProcessingStrategySequenceIsReturned()
    {
        $this->assertInstanceOf(
            ImageProcessingStrategySequence::class,
            $this->factory->createGalleyThumbnailImageProcessingStrategySequence()
        );
    }

    public function testFileUrlKeyStoreIsReturned()
    {
        $this->assertInstanceOf(FileUrlKeyStore::class, $this->factory->createUrlKeyStore());
    }

    public function testItReturnsAnExistingDirectoryAsTheFileStorageBasePathConfig()
    {
        $fileStorageBasePath = $this->factory->getFileStorageBasePathConfig();
        $this->assertInternalType('string', $fileStorageBasePath);
        $this->assertFileExists($fileStorageBasePath);
        $this->assertTrue(is_dir($fileStorageBasePath));
    }

    public function testReturnsProductListingAvailableSortBy()
    {
        $this->assertContainsOnly(SortBy::class, $this->factory->getProductListingAvailableSortBy());
    }

    public function testReturnProductListingDefaultSortBy()
    {
        $this->assertInstanceOf(SortBy::class, $this->factory->getProductListingDefaultSortBy());
    }

    public function testReturnsProductSearchAvailableSortBy()
    {
        $this->assertContainsOnly(SortBy::class, $this->factory->getProductSearchAvailableSortBy());
    }

    public function testReturnProductSearchDefaultSortBy()
    {
        $this->assertInstanceOf(SortBy::class, $this->factory->getProductSearchDefaultSortBy());
    }

    public function testSameInstanceOfProductsPerPageIsReturned()
    {
        $result1 = $this->factory->getProductsPerPageConfig();
        $result2 = $this->factory->getProductsPerPageConfig();

        $this->assertInstanceOf(ProductsPerPage::class, $result1);
        $this->assertSame($result1, $result2);
    }

    public function testItReturnsAWebsiteToCountryMapInstance()
    {
        $this->assertInstanceOf(WebsiteToCountryMap::class, $this->factory->createWebsiteToCountryMap());
    }

    public function testItReturnsATaxableCountryInstance()
    {
        $this->assertInstanceOf(TaxableCountries::class, $this->factory->createTaxableCountries());
    }

    public function testItReturnsATaxServiceLocator()
    {
        $this->assertInstanceOf(TaxServiceLocator::class, $this->factory->createTaxServiceLocator());
    }

    public function testProductViewLocatorIsReturned()
    {
        $this->assertInstanceOf(ProductViewLocator::class, $this->factory->createProductViewLocator());
    }

    public function testItReturnsAProductImageFileLocatorInstance()
    {
        $result = $this->factory->createProductImageFileLocator();
        $this->assertInstanceOf(DemoProjectProductImageFileLocator::class, $result);
    }

    public function testItReturnsAnImageStorage()
    {
        $this->assertInstanceOf(ImageStorage::class, $this->factory->createImageStorage());
    }

    public function testItReturnsASearchFieldToRequestParamMap()
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $stubContext */
        $stubContext = $this->createMock(Context::class);
        $result = $this->factory->createSearchFieldToRequestParamMap($stubContext);
        $this->assertInstanceOf(SearchFieldToRequestParamMap::class, $result);
    }

    public function testItReturnsThePriceFacetFieldName()
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $stubContext */
        $stubContext = $this->createMock(Context::class);
        $stubContext->method('getValue')->willReturn('DE');
        $this->assertSame('price_incl_tax_de', $this->factory->getPriceFacetFieldNameForContext($stubContext));
    }

    public function testDefaultFileLogPathIsReturned()
    {
        $expectedPath = preg_replace(
            '/tests\/Unit\/Suites/',
            'src/lizards-and-pumpkins/src',
            __DIR__ . '/../log/system.log'
        );
        $this->assertSame($expectedPath, $this->factory->getLogFilePathConfig());
    }

    public function testFileLogPathStoredInEnvironmentIsReturned()
    {
        $expectedPath = 'foo';
        $oldPath = $this->changeFileLogPathInEnvironmentConfig($expectedPath);

        $this->assertSame($expectedPath, $this->factory->getLogFilePathConfig());

        $this->changeFileLogPathInEnvironmentConfig($oldPath);
    }

    public function testProductListingTitleSnippetRendererIsReturned()
    {
        $result = $this->factory->createProductListingTitleSnippetRenderer();
        $this->assertInstanceOf(DemoProjectProductListingTitleSnippetRenderer::class, $result);
    }

    public function testThemeLocatorIsReturned()
    {
        $result = $this->factory->createThemeLocator();
        $this->assertInstanceOf(ThemeLocator::class, $result);
    }

    public function testReturnsDemoProjectContextSource()
    {
        $this->assertInstanceOf(DemoProjectContextSource::class, $this->factory->createContextSource());
    }
}
