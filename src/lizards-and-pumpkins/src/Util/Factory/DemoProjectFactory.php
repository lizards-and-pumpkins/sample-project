<?php

namespace LizardsAndPumpkins\Util\Factory;

use LizardsAndPumpkins\Context\Context;
use LizardsAndPumpkins\Context\ContextPartBuilder;
use LizardsAndPumpkins\Context\ContextSource;
use LizardsAndPumpkins\Context\Country\Country;
use LizardsAndPumpkins\Context\Country\DemoProjectCountryContextPartBuilder;
use LizardsAndPumpkins\Context\Locale\Locale;
use LizardsAndPumpkins\Context\Locale\DemoProjectLocaleContextPartBuilder;
use LizardsAndPumpkins\Context\DemoProjectContextSource;
use LizardsAndPumpkins\Context\Website\ConfigurableUrlToWebsiteMap;
use LizardsAndPumpkins\Context\Website\RequestToWebsiteMap;
use LizardsAndPumpkins\Context\Website\DemoProjectWebsiteContextPartBuilder;
use LizardsAndPumpkins\Context\Website\UrlToWebsiteMap;
use LizardsAndPumpkins\Context\Website\WebsiteToCountryMap;
use LizardsAndPumpkins\DataPool\KeyValueStore\File\FileKeyValueStore;
use LizardsAndPumpkins\DataPool\KeyValueStore\KeyValueStore;
use LizardsAndPumpkins\DataPool\SearchEngine\FacetFieldTransformation\CurrencyPriceRangeTransformation;
use LizardsAndPumpkins\DataPool\SearchEngine\FacetFieldTransformation\FacetFieldTransformationRegistry;
use LizardsAndPumpkins\DataPool\SearchEngine\FacetFilterRequestField;
use LizardsAndPumpkins\DataPool\SearchEngine\FacetFilterRequestRangedField;
use LizardsAndPumpkins\DataPool\SearchEngine\FacetFilterRequestSimpleField;
use LizardsAndPumpkins\DataPool\SearchEngine\Filesystem\FileSearchEngine;
use LizardsAndPumpkins\DataPool\SearchEngine\FilterNavigationPriceRangesBuilder;
use LizardsAndPumpkins\DataPool\SearchEngine\Query\SortOrderConfig;
use LizardsAndPumpkins\DataPool\SearchEngine\Query\SortOrderDirection;
use LizardsAndPumpkins\DataPool\SearchEngine\SearchCriteria\CompositeSearchCriterion;
use LizardsAndPumpkins\DataPool\SearchEngine\SearchCriteria\SearchCriteria;
use LizardsAndPumpkins\DataPool\SearchEngine\SearchCriteria\SearchCriterionEqual;
use LizardsAndPumpkins\DataPool\SearchEngine\SearchCriteria\SearchCriterionGreaterThan;
use LizardsAndPumpkins\DataPool\SearchEngine\SearchEngine;
use LizardsAndPumpkins\DataPool\UrlKeyStore\FileUrlKeyStore;
use LizardsAndPumpkins\Import\FileStorage\FileStorageReader;
use LizardsAndPumpkins\Import\FileStorage\FileStorageWriter;
use LizardsAndPumpkins\Import\ImageStorage\FilesystemImageStorage;
use LizardsAndPumpkins\Import\ImageStorage\ImageProcessing\ImageMagick\ImageMagickInscribeStrategy;
use LizardsAndPumpkins\Import\ImageStorage\ImageProcessing\ImageProcessingStrategySequence;
use LizardsAndPumpkins\Import\ImageStorage\ImageProcessing\ImageProcessor;
use LizardsAndPumpkins\Import\ImageStorage\ImageProcessing\ImageProcessorCollection;
use LizardsAndPumpkins\Import\ImageStorage\ImageStorage;
use LizardsAndPumpkins\Import\Product\AttributeCode;
use LizardsAndPumpkins\Import\Product\Image\DemoProjectProductImageFileLocator;
use LizardsAndPumpkins\Import\Product\View\ProductImageFileLocator;
use LizardsAndPumpkins\Import\Product\View\DemoProjectProductViewLocator;
use LizardsAndPumpkins\Import\SnippetRenderer;
use LizardsAndPumpkins\Import\Tax\TaxableCountries;
use LizardsAndPumpkins\Import\Tax\DemoProjectTaxableCountries;
use LizardsAndPumpkins\Import\Tax\DemoProjectTaxServiceLocator;
use LizardsAndPumpkins\Import\TemplateRendering\ThemeLocator;
use LizardsAndPumpkins\Logging\InMemoryLogger;
use LizardsAndPumpkins\Logging\Logger;
use LizardsAndPumpkins\Logging\LogMessageWriter;
use LizardsAndPumpkins\Logging\Writer\CompositeLogMessageWriter;
use LizardsAndPumpkins\Logging\Writer\FileLogMessageWriter;
use LizardsAndPumpkins\Logging\WritingLoggerDecorator;
use LizardsAndPumpkins\Messaging\Queue;
use LizardsAndPumpkins\Messaging\Queue\File\FileQueue;
use LizardsAndPumpkins\ProductDetail\Import\View\DemoProjectProductPageTitle;
use LizardsAndPumpkins\ProductListing\ContentDelivery\ProductsPerPage;
use LizardsAndPumpkins\ProductListing\Import\DemoProjectProductListingTitleSnippetRenderer;
use LizardsAndPumpkins\ProductSearch\ContentDelivery\SearchFieldToRequestParamMap;
use LizardsAndPumpkins\Util\Config\ConfigReader;
use LizardsAndPumpkins\Util\FileSystem\LocalFilesystemStorageReader;
use LizardsAndPumpkins\Util\FileSystem\LocalFilesystemStorageWriter;
use SebastianBergmann\Money\Currency;

class DemoProjectFactory implements Factory
{
    use FactoryTrait;

    /**
     * @var SortOrderConfig[]
     */
    private $memoizedProductListingSortOrderConfig;

    /**
     * @var SortOrderConfig[]
     */
    private $memoizedProductSearchSortOrderConfig;

    /**
     * @var SortOrderConfig
     */
    private $memoizedProductSearchAutosuggestionSortOrderConfig;

    /**
     * @var ProductsPerPage
     */
    private $memoizedProductsPerPageConfig;

    /**
     * @return string[]
     */
    public function getSearchableAttributeCodes()
    {
        return [
            'brand',
            'description',
            'gender',
            'mpn',
            'name',
            'product_group',
            'series',
            'style'
        ];
    }

    /**
     * @param FacetFilterRequestField[] $fields
     * @param string $name
     * @param int $pos
     * @return int
     */
    private function findFacetFieldPosition(array $fields, $name, $pos = 0)
    {
        if ($pos === count($fields) || $fields[$pos]->getAttributeCode() == $name) {
            return $pos;
        }
        return $this->findFacetFieldPosition($fields, $name, $pos + 1);
    }

    /**
     * @param FacetFilterRequestField $fieldToInject
     * @param string $siblingName
     * @param FacetFilterRequestField[] $fields
     * @return FacetFilterRequestField[]
     */
    private function injectFacetFieldAfter(FacetFilterRequestField $fieldToInject, $siblingName, array $fields)
    {
        $pos = $this->findFacetFieldPosition($fields, $siblingName);
        return array_merge(array_slice($fields, 0, $pos + 1), [$fieldToInject], array_slice($fields, $pos + 1));
    }

    /**
     * @param Context $context
     * @return FacetFilterRequestField[]
     */
    public function getProductListingFacetFilterRequestFields(Context $context)
    {
        $priceField = $this->createPriceRangeFacetFilterField($context);
        return $this->injectFacetFieldAfter($priceField, 'brand', $this->getCommonFacetFilterRequestFields());
    }

    /**
     * @param Context $context
     * @return FacetFilterRequestField[]
     */
    public function getProductSearchFacetFilterRequestFields(Context $context)
    {
        $priceField = $this->createPriceRangeFacetFilterField($context);
        return $this->injectFacetFieldAfter($priceField, 'brand', $this->getCommonFacetFilterRequestFields());
    }

    /**
     * @return string[]
     */
    public function getFacetFilterRequestFieldCodesForSearchDocuments()
    {
        return array_map(function (FacetFilterRequestField $field) {
            return (string) $field->getAttributeCode();
        }, $this->getCommonFacetFilterRequestFields());
    }

    /**
     * @return FacetFilterRequestField[]
     */
    private function getCommonFacetFilterRequestFields()
    {
        return [
            new FacetFilterRequestSimpleField(AttributeCode::fromString('gender')),
            new FacetFilterRequestSimpleField(AttributeCode::fromString('product_group')),
            new FacetFilterRequestSimpleField(AttributeCode::fromString('style')),
            new FacetFilterRequestSimpleField(AttributeCode::fromString('brand')),
            new FacetFilterRequestSimpleField(AttributeCode::fromString('series')),
            new FacetFilterRequestSimpleField(AttributeCode::fromString('size')),
            new FacetFilterRequestSimpleField(AttributeCode::fromString('color')),
        ];
    }

    /**
     * @param Context $context
     * @return FacetFilterRequestField
     */
    private function createPriceRangeFacetFilterField(Context $context)
    {
        return new FacetFilterRequestRangedField(
            AttributeCode::fromString($this->getPriceFacetFieldNameForContext($context)),
            ...FilterNavigationPriceRangesBuilder::getPriceRanges()
        );
    }

    /**
     * @param Context $context
     * @return string
     */
    public function getPriceFacetFieldNameForContext(Context $context)
    {
        return $this->getPriceFacetFieldNameForCountry($context->getValue(Country::CONTEXT_CODE));
    }

    /**
     * @param string $countryCode
     * @return string
     */
    private function getPriceFacetFieldNameForCountry($countryCode)
    {
        return 'price_incl_tax_' . strtolower($countryCode);
    }

    /**
     * @return string[]
     */
    public function getAdditionalAttributesForSearchIndex()
    {
        return ['backorders', 'stock_qty', 'category', 'created_at'];
    }

    /**
     * @return KeyValueStore
     */
    public function createKeyValueStore()
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        $storagePath = $storageBasePath . '/key-value-store';
        $this->createDirectoryIfNotExists($storagePath);

        return new FileKeyValueStore($storagePath);
    }

    /**
     * @return Queue
     */
    public function createEventQueue()
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        $storagePath = $storageBasePath . '/event-queue/content';
        $lockFile = $storageBasePath . '/event-queue/lock';

        return new FileQueue($storagePath, $lockFile);
    }

    /**
     * @return Queue
     */
    public function createCommandQueue()
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        $storagePath = $storageBasePath . '/command-queue/content';
        $lockFile = $storageBasePath . '/command-queue/lock';

        return new FileQueue($storagePath, $lockFile);
    }

    /**
     * @return Logger
     */
    public function createLogger()
    {
        return new WritingLoggerDecorator(
            new InMemoryLogger(),
            $this->getMasterFactory()->createLogMessageWriter()
        );
    }

    /**
     * @return LogMessageWriter
     */
    public function createLogMessageWriter()
    {
        $writers = [
            new FileLogMessageWriter($this->getMasterFactory()->getLogFilePathConfig()),
        ];
        return new CompositeLogMessageWriter(...$writers);
    }

    /**
     * @return string
     */
    public function getLogFilePathConfig()
    {
        /** @var ConfigReader $configReader */
        $configReader = $this->getMasterFactory()->createConfigReader();
        $envConfigValue = $configReader->get('log_file_path');

        if (null !== $envConfigValue) {
            return $envConfigValue;
        }

        return __DIR__ . '/../log/system.log';
    }

    /**
     * @return SearchEngine
     */
    public function createSearchEngine()
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        $storagePath = $storageBasePath . '/search-engine';
        $this->createDirectoryIfNotExists($storagePath);
        
        return FileSearchEngine::create(
            $storagePath,
            $this->getMasterFactory()->getSearchableAttributeCodes(),
            $this->getMasterFactory()->createSearchCriteriaBuilder(),
            $this->getMasterFactory()->getFacetFieldTransformationRegistry()
        );
    }

    /**
     * @return FileUrlKeyStore
     */
    public function createUrlKeyStore()
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        return new FileUrlKeyStore($storageBasePath . '/url-key-store');
    }

    /**
     * @return FacetFieldTransformationRegistry
     */
    public function createFacetFieldTransformationRegistry()
    {
        $registry = new FacetFieldTransformationRegistry();
        $priceTransformation = $this->createEuroPriceRangeTransformation();
        $registry->register('price', $priceTransformation);
        $countries = $this->getMasterFactory()->createTaxableCountries()->getCountries();
        array_map(function ($country) use ($registry, $priceTransformation) {
            $registry->register($this->getPriceFacetFieldNameForCountry($country), $priceTransformation);
        }, $countries);

        return $registry;
    }

    /**
     * @return CurrencyPriceRangeTransformation
     */
    private function createEuroPriceRangeTransformation()
    {
        // Note: unable to use context directly to determine locale here due to circular dependency
        $localFactory = function () {
            return $this->getMasterFactory()->createContext()->getValue(Locale::CONTEXT_CODE);
        };
        return new CurrencyPriceRangeTransformation(new Currency('EUR'), $localFactory);
    }

    /**
     * @return ImageProcessorCollection
     */
    public function createImageProcessorCollection()
    {
        $processorCollection = new ImageProcessorCollection();
        $processorCollection->add($this->getMasterFactory()->createOriginalImageProcessor());
        $processorCollection->add($this->getMasterFactory()->createProductDetailsPageImageProcessor());
        $processorCollection->add($this->getMasterFactory()->createProductListingImageProcessor());
        $processorCollection->add($this->getMasterFactory()->createGalleyThumbnailImageProcessor());
        $processorCollection->add($this->getMasterFactory()->createSearchAutosuggestionImageProcessor());

        return $processorCollection;
    }

    /**
     * @return ImageProcessor
     */
    public function createOriginalImageProcessor()
    {
        $strategySequence = $this->getMasterFactory()->createOriginalImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::ORIGINAL;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    /**
     * @return FileStorageReader
     */
    public function createFileStorageReader()
    {
        return new LocalFilesystemStorageReader();
    }

    /**
     * @return FileStorageWriter
     */
    public function createFileStorageWriter()
    {
        return new LocalFilesystemStorageWriter();
    }

    /**
     * @return ImageProcessingStrategySequence
     */
    public function createOriginalImageProcessingStrategySequence()
    {
        return new ImageProcessingStrategySequence();
    }

    /**
     * @return ImageProcessor
     */
    public function createProductDetailsPageImageProcessor()
    {
        $strategySequence = $this->getMasterFactory()->createProductDetailsPageImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::LARGE;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    /**
     * @return ImageProcessingStrategySequence
     */
    public function createProductDetailsPageImageProcessingStrategySequence()
    {
        $imageResizeStrategy = new ImageMagickInscribeStrategy(365, 340, 'white');

        $strategySequence = new ImageProcessingStrategySequence();
        $strategySequence->add($imageResizeStrategy);

        return $strategySequence;
    }

    /**
     * @return ImageProcessor
     */
    public function createProductListingImageProcessor()
    {
        $strategySequence = $this->getMasterFactory()->createProductListingImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::MEDIUM;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    /**
     * @return ImageProcessingStrategySequence
     */
    public function createProductListingImageProcessingStrategySequence()
    {
        $imageResizeStrategy = new ImageMagickInscribeStrategy(188, 115, 'white');

        $strategySequence = new ImageProcessingStrategySequence();
        $strategySequence->add($imageResizeStrategy);

        return $strategySequence;
    }

    /**
     * @return ImageProcessor
     */
    public function createGalleyThumbnailImageProcessor()
    {
        $strategySequence = $this->getMasterFactory()->createGalleyThumbnailImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::SMALL;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    /**
     * @return ImageProcessingStrategySequence
     */
    public function createGalleyThumbnailImageProcessingStrategySequence()
    {
        $imageResizeStrategy = new ImageMagickInscribeStrategy(48, 48, 'white');

        $strategySequence = new ImageProcessingStrategySequence();
        $strategySequence->add($imageResizeStrategy);

        return $strategySequence;
    }

    /**
     * @return ImageProcessor
     */
    public function createSearchAutosuggestionImageProcessor()
    {
        $strategySequence = $this->getMasterFactory()->createSearchAutosuggestionImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::SEARCH_AUTOSUGGESTION;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    /**
     * @return ImageProcessingStrategySequence
     */
    public function createSearchAutosuggestionImageProcessingStrategySequence()
    {
        $imageResizeStrategy = new ImageMagickInscribeStrategy(60, 37, 'white');

        $strategySequence = new ImageProcessingStrategySequence();
        $strategySequence->add($imageResizeStrategy);

        return $strategySequence;
    }

    /**
     * @return string
     */
    public function getFileStorageBasePathConfig()
    {
        /** @var ConfigReader $configReader */
        $configReader = $this->getMasterFactory()->createConfigReader();
        $basePath = $configReader->get('file_storage_base_path');
        return null === $basePath ?
            sys_get_temp_dir() . '/lizards-and-pumpkins' :
            $basePath;
    }

    /**
     * @param string $path
     */
    private function createDirectoryIfNotExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    /**
     * @return SortOrderConfig[]
     */
    public function getProductListingSortOrderConfig()
    {
        if (null === $this->memoizedProductListingSortOrderConfig) {
            $this->memoizedProductListingSortOrderConfig = [
                SortOrderConfig::createSelected(
                    AttributeCode::fromString('name'),
                    SortOrderDirection::create(SortOrderDirection::ASC)
                ),
                SortOrderConfig::create(
                    AttributeCode::fromString('price'),
                    SortOrderDirection::create(SortOrderDirection::ASC)
                ),
                SortOrderConfig::create(
                    AttributeCode::fromString('created_at'),
                    SortOrderDirection::create(SortOrderDirection::DESC)
                ),
            ];
        }

        return $this->memoizedProductListingSortOrderConfig;
    }

    /**
     * @return SortOrderConfig[]
     */
    public function getProductSearchSortOrderConfig()
    {
        if (null === $this->memoizedProductSearchSortOrderConfig) {
            $this->memoizedProductSearchSortOrderConfig = [
                SortOrderConfig::createSelected(
                    AttributeCode::fromString('name'),
                    SortOrderDirection::create(SortOrderDirection::ASC)
                ),
                SortOrderConfig::create(
                    AttributeCode::fromString('price'),
                    SortOrderDirection::create(SortOrderDirection::ASC)
                ),
                SortOrderConfig::create(
                    AttributeCode::fromString('created_at'),
                    SortOrderDirection::create(SortOrderDirection::ASC)
                ),
            ];
        }

        return $this->memoizedProductSearchSortOrderConfig;
    }

    /**
     * @return SortOrderConfig
     */
    public function getProductSearchAutosuggestionSortOrderConfig()
    {
        if (null === $this->memoizedProductSearchAutosuggestionSortOrderConfig) {
            $this->memoizedProductSearchAutosuggestionSortOrderConfig = SortOrderConfig::createSelected(
                AttributeCode::fromString('name'),
                SortOrderDirection::create(SortOrderDirection::ASC)
            );
        }

        return $this->memoizedProductSearchAutosuggestionSortOrderConfig;
    }

    /**
     * @return ProductsPerPage
     */
    public function getProductsPerPageConfig()
    {
        if (null === $this->memoizedProductsPerPageConfig) {
            $numbersOfProductsPerPage = [60, 120];
            $selectedNumberOfProductsPerPage = 60;

            $this->memoizedProductsPerPageConfig = ProductsPerPage::create(
                $numbersOfProductsPerPage,
                $selectedNumberOfProductsPerPage
            );
        }

        return $this->memoizedProductsPerPageConfig;
    }

    /**
     * @return WebsiteToCountryMap
     */
    public function createWebsiteToCountryMap()
    {
        return new WebsiteToCountryMap();
    }

    /**
     * @return TaxableCountries
     */
    public function createTaxableCountries()
    {
        return new DemoProjectTaxableCountries();
    }

    /**
     * @return DemoProjectTaxServiceLocator
     */
    public function createTaxServiceLocator()
    {
        return new DemoProjectTaxServiceLocator();
    }

    /**
     * @return DemoProjectProductViewLocator
     */
    public function createProductViewLocator()
    {
        return new DemoProjectProductViewLocator(
            $this->getMasterFactory()->createProductImageFileLocator(),
            $this->getMasterFactory()->createProductTitle()
        );
    }

    /**
     * @return DemoProjectProductPageTitle
     */
    public function createProductTitle()
    {
        return new DemoProjectProductPageTitle();
    }

    /**
     * @return SearchCriteria
     */
    public function createGlobalProductListingCriteria()
    {
        return CompositeSearchCriterion::createOr(
            SearchCriterionGreaterThan::create('stock_qty', 0),
            SearchCriterionEqual::create('backorders', 'true')
        );
    }

    /**
     * @return ProductImageFileLocator
     */
    public function createProductImageFileLocator()
    {
        return new DemoProjectProductImageFileLocator(
            $this->getMasterFactory()->createImageStorage()
        );
    }

    /**
     * @return ImageStorage
     */
    public function createImageStorage()
    {
        return new FilesystemImageStorage(
            $this->getMasterFactory()->createFilesystemFileStorage(),
            $this->getMasterFactory()->createMediaBaseUrlBuilder(),
            $this->getMasterFactory()->getMediaBaseDirectoryConfig()
        );
    }

    /**
     * @param Context $context
     * @return SearchFieldToRequestParamMap
     */
    public function createSearchFieldToRequestParamMap(Context $context)
    {
        $queryParameter = 'price';
        $facetField = $this->getPriceFacetFieldNameForContext($context);
        $facetFieldToQueryParameterMap = [$facetField => $queryParameter];
        $queryParameterToFacetFieldMap = [$queryParameter => $facetField];
        return new SearchFieldToRequestParamMap($facetFieldToQueryParameterMap, $queryParameterToFacetFieldMap);
    }

    /**
     * @return SnippetRenderer
     */
    public function createProductListingTitleSnippetRenderer()
    {
        return new DemoProjectProductListingTitleSnippetRenderer(
            $this->getMasterFactory()->createProductListingTitleSnippetKeyGenerator(),
            $this->getMasterFactory()->createContextBuilder()
        );
    }

    /**
     * @return ThemeLocator
     */
    public function createThemeLocator()
    {
        return ThemeLocator::fromPath(__DIR__ . '/../../..');
    }

    /**
     * @return ContextSource
     */
    public function createContextSource()
    {
        return new DemoProjectContextSource(
            $this->getMasterFactory()->createContextBuilder()
        );
    }

    /**
     * @return ContextPartBuilder
     */
    public function createWebsiteContextPartBuilder()
    {
        return new DemoProjectWebsiteContextPartBuilder(
            $this->createRequestToWebsiteMap()
        );
    }
    
    /**
     * @return ContextPartBuilder
     */
    public function createCountryContextPartBuilder()
    {
        return new DemoProjectCountryContextPartBuilder(
            $this->createRequestToWebsiteMap(),
            $this->getMasterFactory()->createWebsiteToCountryMap()
        );
    }

    /**
     * @return RequestToWebsiteMap
     */
    private function createRequestToWebsiteMap()
    {
        return new RequestToWebsiteMap($this->getMasterFactory()->createUrlToWebsiteMap());
    }

    /**
     * @return UrlToWebsiteMap
     */
    public function createUrlToWebsiteMap()
    {
        return ConfigurableUrlToWebsiteMap::fromConfig($this->getMasterFactory()->createConfigReader());
    }

    /**
     * @return ContextPartBuilder
     */
    public function createLocaleContextPartBuilder()
    {
        return new DemoProjectLocaleContextPartBuilder($this->createRequestToWebsiteMap());
    }
}
