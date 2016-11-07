<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Product\Image;

use LizardsAndPumpkins\Context\Context;
use LizardsAndPumpkins\Context\Locale\Locale;
use LizardsAndPumpkins\Import\FileStorage\File;
use LizardsAndPumpkins\Import\FileStorage\StorageAgnosticFileUri;
use LizardsAndPumpkins\Import\ImageStorage\Exception\InvalidImageVariantCodeException;
use LizardsAndPumpkins\Import\ImageStorage\ImageStorage;
use LizardsAndPumpkins\Import\Product\View\ProductImageFileLocator;

class DemoProjectProductImageFileLocator implements ProductImageFileLocator
{
    const ORIGINAL = 'original';
    const LARGE = 'large';
    const MEDIUM = 'medium';
    const SMALL = 'small';
    const SEARCH_AUTOSUGGESTION = 'search-autosuggestion';

    private $imageVariantCodes = [
        self::ORIGINAL,
        self::LARGE,
        self::MEDIUM,
        self::SMALL,
        self::SEARCH_AUTOSUGGESTION,
    ];

    /**
     * @var ImageStorage
     */
    private $imageStorage;

    public function __construct(ImageStorage $imageStorage)
    {
        $this->imageStorage = $imageStorage;
    }

    public function get(string $imageFileName, string $imageVariantCode, Context $context) : File
    {
        $this->validateImageVariantCode($imageVariantCode);

        if (! $this->isImageFileAvailable($imageFileName)) {
            return $this->getPlaceholder($imageVariantCode, $context);
        }

        $imageIdentifier = $this->buildIdentifier($imageFileName, $imageVariantCode);
        return $this->imageStorage->getFileReference($imageIdentifier);
    }

    private function buildIdentifier(string $imageFileName, string $imageVariantCode) : StorageAgnosticFileUri
    {
        return $this->createIdentifierForString(sprintf('product/%s/%s', $imageVariantCode, $imageFileName));
    }

    private function createIdentifierForString(string $identifier) : StorageAgnosticFileUri
    {
        return StorageAgnosticFileUri::fromString($identifier);
    }

    public function getPlaceholder(string $imageVariantCode, Context $context) : File
    {
        $localeCode = $context->getValue(Locale::CONTEXT_CODE);
        $identifier = sprintf('product/placeholder/%s/placeholder-image-%s.jpg', $imageVariantCode, $localeCode);
        $placeholderIdentifier = $this->createIdentifierForString($identifier);
        return $this->imageStorage->getFileReference($placeholderIdentifier);
    }

    private function validateImageVariantCode(string $imageVariantCode)
    {
        if (!in_array($imageVariantCode, $this->imageVariantCodes)) {
            throw new InvalidImageVariantCodeException(sprintf(
                'The image variant code must be one of %s, got "%s"',
                implode(', ', $this->imageVariantCodes),
                $imageVariantCode
            ));
        }
    }

    /**
     * @return string[]
     */
    public function getVariantCodes() : array
    {
        return $this->imageVariantCodes;
    }

    private function isImageFileAvailable(string $imageFileName) : bool
    {
        return trim($imageFileName) !== '';
    }
}
