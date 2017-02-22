<?php

declare(strict_types=1);

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
use LizardsAndPumpkins\DataPool\SearchEngine\Query\SortBy;
use LizardsAndPumpkins\DataPool\SearchEngine\Query\SortDirection;
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
use LizardsAndPumpkins\Messaging\Command\CommandQueue;
use LizardsAndPumpkins\Messaging\Event\DomainEventQueue;
use LizardsAndPumpkins\Messaging\MessageQueueFactory;
use LizardsAndPumpkins\Messaging\Queue;
use LizardsAndPumpkins\Messaging\Queue\File\FileQueue;
use LizardsAndPumpkins\ProductDetail\Import\View\DemoProjectProductPageTitle;
use LizardsAndPumpkins\ProductListing\ContentDelivery\ProductsPerPage;
use LizardsAndPumpkins\ProductListing\Import\DemoProjectProductListingTitleSnippetRenderer;
use LizardsAndPumpkins\ProductSearch\ContentDelivery\ProductSearchFactory;
use LizardsAndPumpkins\ProductSearch\ContentDelivery\SearchFieldToRequestParamMap;
use LizardsAndPumpkins\RestApi\RestApiFactory;
use LizardsAndPumpkins\Util\Config\ConfigReader;
use LizardsAndPumpkins\Util\FileSystem\LocalFilesystemStorageReader;
use LizardsAndPumpkins\Util\FileSystem\LocalFilesystemStorageWriter;
use SebastianBergmann\Money\Currency;

class ProjectFactory implements Factory, MessageQueueFactory, FactoryWithCallback
{
    use FactoryTrait;

    /**
     * @var ProductsPerPage
     */
    private $memoizedProductsPerPageConfig;

    /**
     * @var CommandQueue
     */
    private $eventMessageQueue;

    /**
     * @var CommandQueue
     */
    private $commandMessageQueue;

    /**
     * @var DomainEventQueue
     */
    private $eventQueue;

    /**
     * @var CommandQueue
     */
    private $commandQueue;

    /**
     * @return string[]
     */
    public function getSearchableAttributeCodes() : array
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

    public function factoryRegistrationCallback(MasterFactory $masterFactory)
    {
        $masterFactory->register(new RestApiFactory());
        $masterFactory->register(new ProductSearchFactory());
    }

    /**
     * @param FacetFilterRequestField[] $fields
     * @param string $name
     * @param int $pos
     * @return int
     */
    private function findFacetFieldPosition(array $fields, string $name, int $pos = 0) : int
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
    private function injectFacetFieldAfter(
        FacetFilterRequestField $fieldToInject,
        string $siblingName,
        array $fields
    ) : array {
        $pos = $this->findFacetFieldPosition($fields, $siblingName);
        return array_merge(array_slice($fields, 0, $pos + 1), [$fieldToInject], array_slice($fields, $pos + 1));
    }

    /**
     * @param Context $context
     * @return FacetFilterRequestField[]
     */
    public function getProductListingFacetFilterRequestFields(Context $context) : array
    {
        $priceField = $this->createPriceRangeFacetFilterField($context);
        return $this->injectFacetFieldAfter($priceField, 'brand', $this->getCommonFacetFilterRequestFields());
    }

    /**
     * @param Context $context
     * @return FacetFilterRequestField[]
     */
    public function getProductSearchFacetFilterRequestFields(Context $context) : array
    {
        $priceField = $this->createPriceRangeFacetFilterField($context);
        return $this->injectFacetFieldAfter($priceField, 'brand', $this->getCommonFacetFilterRequestFields());
    }

    /**
     * @return string[]
     */
    public function getFacetFilterRequestFieldCodesForSearchDocuments() : array
    {
        return array_map(function (FacetFilterRequestField $field) {
            return (string) $field->getAttributeCode();
        }, $this->getCommonFacetFilterRequestFields());
    }

    /**
     * @return FacetFilterRequestField[]
     */
    private function getCommonFacetFilterRequestFields() : array
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

    private function createPriceRangeFacetFilterField(Context $context) : FacetFilterRequestField
    {
        return new FacetFilterRequestRangedField(
            AttributeCode::fromString($this->getPriceFacetFieldNameForContext($context)),
            ...FilterNavigationPriceRangesBuilder::getPriceRanges()
        );
    }

    public function getPriceFacetFieldNameForContext(Context $context) : string
    {
        return $this->getPriceFacetFieldNameForCountry($context->getValue(Country::CONTEXT_CODE));
    }

    private function getPriceFacetFieldNameForCountry(string $countryCode) : string
    {
        return 'price_incl_tax_' . strtolower($countryCode);
    }

    /**
     * @return string[]
     */
    public function getSortableAttributeCodes() : array
    {
        return ['backorders', 'stock_qty', 'category', 'created_at'];
    }

    public function createKeyValueStore() : KeyValueStore
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        $storagePath = $storageBasePath . '/key-value-store';
        $this->createDirectoryIfNotExists($storagePath);

        return new FileKeyValueStore($storagePath);
    }

    public function getEventQueue() : DomainEventQueue
    {
        if (null === $this->eventQueue) {
            $this->eventQueue = $this->getMasterFactory()->createEventQueue();
        }
        return $this->eventQueue;
    }

    public function createEventQueue() : DomainEventQueue
    {
        return new DomainEventQueue($this->getEventMessageQueue());
    }

    public function getEventMessageQueue() : Queue
    {
        if (null === $this->eventMessageQueue) {
            $this->eventMessageQueue = $this->getMasterFactory()->createEventMessageQueue();
        }
        return $this->eventMessageQueue;
    }

    public function createEventMessageQueue() : Queue
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        $storagePath = $storageBasePath . '/event-queue/content';
        $lockFile = $storageBasePath . '/event-queue/lock';

        return new FileQueue($storagePath, $lockFile);
    }

    public function getCommandQueue() : CommandQueue
    {
        if (null === $this->commandQueue) {
            $this->commandQueue = $this->getMasterFactory()->createCommandQueue();
        }
        return $this->commandQueue;
    }

    public function createCommandQueue() : CommandQueue
    {
        return new CommandQueue($this->getCommandMessageQueue());
    }

    public function getCommandMessageQueue() : Queue
    {
        if (null === $this->commandMessageQueue) {
            $this->commandMessageQueue = $this->getMasterFactory()->createCommandMessageQueue();
        }
        return $this->commandMessageQueue;
    }

    public function createCommandMessageQueue() : Queue
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        $storagePath = $storageBasePath . '/command-queue/content';
        $lockFile = $storageBasePath . '/command-queue/lock';

        return new FileQueue($storagePath, $lockFile);
    }

    public function createLogger() : Logger
    {
        return new WritingLoggerDecorator(
            new InMemoryLogger(),
            $this->getMasterFactory()->createLogMessageWriter()
        );
    }

    public function createLogMessageWriter() : LogMessageWriter
    {
        $writers = [
            new FileLogMessageWriter($this->getMasterFactory()->getLogFilePathConfig()),
        ];
        return new CompositeLogMessageWriter(...$writers);
    }

    public function getLogFilePathConfig() : string
    {
        /** @var ConfigReader $configReader */
        $configReader = $this->getMasterFactory()->createConfigReader();
        if ($configReader->has('log_file_path')) {
            return $configReader->get('log_file_path');;
        }

        return __DIR__ . '/../log/system.log';
    }

    public function createSearchEngine() : SearchEngine
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        $storagePath = $storageBasePath . '/search-engine';
        $this->createDirectoryIfNotExists($storagePath);
        
        return FileSearchEngine::create(
            $storagePath,
            $this->getMasterFactory()->getSearchableAttributeCodes(),
            $this->getMasterFactory()->getFacetFieldTransformationRegistry()
        );
    }

    public function createUrlKeyStore() : FileUrlKeyStore
    {
        $storageBasePath = $this->getMasterFactory()->getFileStorageBasePathConfig();
        return new FileUrlKeyStore($storageBasePath . '/url-key-store');
    }

    public function createFacetFieldTransformationRegistry() : FacetFieldTransformationRegistry
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

    private function createEuroPriceRangeTransformation() : CurrencyPriceRangeTransformation
    {
        // Note: unable to use context directly to determine locale here due to circular dependency
        $localFactory = function () {
            return $this->getMasterFactory()->createContext()->getValue(Locale::CONTEXT_CODE);
        };
        return new CurrencyPriceRangeTransformation(new Currency('EUR'), $localFactory);
    }

    public function createImageProcessorCollection() : ImageProcessorCollection
    {
        $processorCollection = new ImageProcessorCollection();
        $processorCollection->add($this->getMasterFactory()->createOriginalImageProcessor());
        $processorCollection->add($this->getMasterFactory()->createProductDetailsPageImageProcessor());
        $processorCollection->add($this->getMasterFactory()->createProductListingImageProcessor());
        $processorCollection->add($this->getMasterFactory()->createGalleyThumbnailImageProcessor());
        $processorCollection->add($this->getMasterFactory()->createSearchAutosuggestionImageProcessor());

        return $processorCollection;
    }

    public function createOriginalImageProcessor() : ImageProcessor
    {
        $strategySequence = $this->getMasterFactory()->createOriginalImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::ORIGINAL;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    public function createFileStorageReader() : FileStorageReader
    {
        return new LocalFilesystemStorageReader();
    }

    public function createFileStorageWriter() : FileStorageWriter
    {
        return new LocalFilesystemStorageWriter();
    }

    public function createOriginalImageProcessingStrategySequence() : ImageProcessingStrategySequence
    {
        return new ImageProcessingStrategySequence();
    }

    public function createProductDetailsPageImageProcessor() : ImageProcessor
    {
        $strategySequence = $this->getMasterFactory()->createProductDetailsPageImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::LARGE;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    public function createProductDetailsPageImageProcessingStrategySequence() : ImageProcessingStrategySequence
    {
        $imageResizeStrategy = new ImageMagickInscribeStrategy(489, 489, 'white');

        $strategySequence = new ImageProcessingStrategySequence();
        $strategySequence->add($imageResizeStrategy);

        return $strategySequence;
    }

    public function createProductListingImageProcessor() : ImageProcessor
    {
        $strategySequence = $this->getMasterFactory()->createProductListingImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::MEDIUM;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    public function createProductListingImageProcessingStrategySequence() : ImageProcessingStrategySequence
    {
        $imageResizeStrategy = new ImageMagickInscribeStrategy(160, 160, 'white');

        $strategySequence = new ImageProcessingStrategySequence();
        $strategySequence->add($imageResizeStrategy);

        return $strategySequence;
    }

    public function createGalleyThumbnailImageProcessor() : ImageProcessor
    {
        $strategySequence = $this->getMasterFactory()->createGalleyThumbnailImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::SMALL;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    public function createGalleyThumbnailImageProcessingStrategySequence() : ImageProcessingStrategySequence
    {
        $imageResizeStrategy = new ImageMagickInscribeStrategy(56, 56, 'white');

        $strategySequence = new ImageProcessingStrategySequence();
        $strategySequence->add($imageResizeStrategy);

        return $strategySequence;
    }

    public function createSearchAutosuggestionImageProcessor() : ImageProcessor
    {
        $strategySequence = $this->getMasterFactory()->createSearchAutosuggestionImageProcessingStrategySequence();
        $fileStorageReader = $this->getMasterFactory()->createFileStorageReader();
        $fileStorageWriter = $this->getMasterFactory()->createFileStorageWriter();

        $resultImageDir = $this->getMasterFactory()->getMediaBaseDirectoryConfig() . '/product/' .
                          DemoProjectProductImageFileLocator::SEARCH_AUTOSUGGESTION;

        $this->createDirectoryIfNotExists($resultImageDir);

        return new ImageProcessor($strategySequence, $fileStorageReader, $fileStorageWriter, $resultImageDir);
    }

    public function createSearchAutosuggestionImageProcessingStrategySequence() : ImageProcessingStrategySequence
    {
        $imageResizeStrategy = new ImageMagickInscribeStrategy(60, 37, 'white');

        $strategySequence = new ImageProcessingStrategySequence();
        $strategySequence->add($imageResizeStrategy);

        return $strategySequence;
    }

    public function getFileStorageBasePathConfig() : string
    {
        /** @var ConfigReader $configReader */
        $configReader = $this->getMasterFactory()->createConfigReader();
        $basePath = $configReader->get('file_storage_base_path');
        return null === $basePath ?
            sys_get_temp_dir() . '/lizards-and-pumpkins' :
            $basePath;
    }

    private function createDirectoryIfNotExists(string $path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    /**
     * @return SortBy[]
     */
    public function getProductListingAvailableSortBy() : array
    {
        return [
            new SortBy(AttributeCode::fromString('name'), SortDirection::create(SortDirection::ASC)),
            new SortBy(AttributeCode::fromString('price'), SortDirection::create(SortDirection::ASC)),
            new SortBy(AttributeCode::fromString('created_at'), SortDirection::create(SortDirection::DESC)),
        ];
    }

    public function getProductListingDefaultSortBy() : SortBy
    {
        return new SortBy(AttributeCode::fromString('name'), SortDirection::create(SortDirection::ASC));
    }

    /**
     * @return SortBy[]
     */
    public function getProductSearchAvailableSortBy() : array
    {
        return [
            new SortBy(AttributeCode::fromString('name'), SortDirection::create(SortDirection::ASC)),
            new SortBy(AttributeCode::fromString('price'), SortDirection::create(SortDirection::ASC)),
            new SortBy(AttributeCode::fromString('created_at'), SortDirection::create(SortDirection::DESC)),
        ];
    }

    public function getProductSearchDefaultSortBy() : SortBy
    {
        return new SortBy(AttributeCode::fromString('created_at'), SortDirection::create(SortDirection::DESC));
    }

    public function getProductsPerPageConfig() : ProductsPerPage
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

    public function createWebsiteToCountryMap() : WebsiteToCountryMap
    {
        return new WebsiteToCountryMap();
    }

    public function createTaxableCountries() : TaxableCountries
    {
        return new DemoProjectTaxableCountries();
    }

    public function createTaxServiceLocator() : DemoProjectTaxServiceLocator
    {
        return new DemoProjectTaxServiceLocator();
    }

    public function createProductViewLocator() : DemoProjectProductViewLocator
    {
        return new DemoProjectProductViewLocator(
            $this->getMasterFactory()->createProductImageFileLocator(),
            $this->getMasterFactory()->createProductTitle()
        );
    }

    public function createProductTitle() : DemoProjectProductPageTitle
    {
        return new DemoProjectProductPageTitle();
    }

    public function createGlobalProductListingCriteria() : SearchCriteria
    {
        return CompositeSearchCriterion::createOr(
            new SearchCriterionGreaterThan('stock_qty', 0),
            new SearchCriterionEqual('backorders', 'true')
        );
    }

    public function createProductImageFileLocator() : ProductImageFileLocator
    {
        return new DemoProjectProductImageFileLocator(
            $this->getMasterFactory()->createImageStorage()
        );
    }

    public function createImageStorage() : ImageStorage
    {
        return new FilesystemImageStorage(
            $this->getMasterFactory()->createFilesystemFileStorage(),
            $this->getMasterFactory()->createMediaBaseUrlBuilder(),
            $this->getMasterFactory()->getMediaBaseDirectoryConfig()
        );
    }

    public function createSearchFieldToRequestParamMap(Context $context) : SearchFieldToRequestParamMap
    {
        $queryParameter = 'price';
        $facetField = $this->getPriceFacetFieldNameForContext($context);
        $facetFieldToQueryParameterMap = [$facetField => $queryParameter];
        $queryParameterToFacetFieldMap = [$queryParameter => $facetField];
        return new SearchFieldToRequestParamMap($facetFieldToQueryParameterMap, $queryParameterToFacetFieldMap);
    }

    public function createProductListingTitleSnippetRenderer() : SnippetRenderer
    {
        return new DemoProjectProductListingTitleSnippetRenderer(
            $this->getMasterFactory()->createProductListingTitleSnippetKeyGenerator(),
            $this->getMasterFactory()->createContextBuilder()
        );
    }

    public function createThemeLocator() : ThemeLocator
    {
        return new ThemeLocator(__DIR__ . '/../../..');
    }

    public function createContextSource() : ContextSource
    {
        return new DemoProjectContextSource(
            $this->getMasterFactory()->createContextBuilder()
        );
    }

    public function createWebsiteContextPartBuilder() : ContextPartBuilder
    {
        return new DemoProjectWebsiteContextPartBuilder(
            $this->createRequestToWebsiteMap()
        );
    }
    
    public function createCountryContextPartBuilder() : ContextPartBuilder
    {
        return new DemoProjectCountryContextPartBuilder(
            $this->createRequestToWebsiteMap(),
            $this->getMasterFactory()->createWebsiteToCountryMap()
        );
    }

    private function createRequestToWebsiteMap() : RequestToWebsiteMap
    {
        return new RequestToWebsiteMap($this->getMasterFactory()->createUrlToWebsiteMap());
    }

    public function createUrlToWebsiteMap() : UrlToWebsiteMap
    {
        return ConfigurableUrlToWebsiteMap::fromConfig($this->getMasterFactory()->createConfigReader());
    }

    public function createLocaleContextPartBuilder() : ContextPartBuilder
    {
        return new DemoProjectLocaleContextPartBuilder($this->createRequestToWebsiteMap());
    }

    public function getMaxAllowedProductsPerSearchResultsPage() : int
    {
        return 120;
    }

    public function getDefaultNumberOfProductsPerSearchResultsPage() : int
    {
        return 20;
    }
}
