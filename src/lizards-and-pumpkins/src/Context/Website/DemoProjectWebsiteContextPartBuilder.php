<?php

namespace LizardsAndPumpkins\Context\Website;

use LizardsAndPumpkins\Context\ContextBuilder;
use LizardsAndPumpkins\Context\ContextPartBuilder;
use LizardsAndPumpkins\Context\Website\Exception\UnableToDetermineContextWebsiteException;

class DemoProjectWebsiteContextPartBuilder implements ContextPartBuilder
{
    /**
     * @var RequestToWebsiteMap
     */
    private $requestToWebsiteMap;

    public function __construct(RequestToWebsiteMap $requestToWebsiteMap)
    {
        $this->requestToWebsiteMap = $requestToWebsiteMap;
    }

    /**
     * @param mixed[] $inputDataSet
     * @return string|null
     */
    public function getValue(array $inputDataSet)
    {
        if (isset($inputDataSet[Website::CONTEXT_CODE])) {
            return (string) $inputDataSet[Website::CONTEXT_CODE];
        }

        if (isset($inputDataSet[ContextBuilder::REQUEST])) {
            return (string) $this->requestToWebsiteMap->getWebsiteFromRequest($inputDataSet[ContextBuilder::REQUEST]);
        }

        throw new UnableToDetermineContextWebsiteException(
            'Unable to determine context website as neither the website nor the request are set in the input array.'
        );
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return Website::CONTEXT_CODE;
    }
}
