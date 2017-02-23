<?php

declare(strict_types = 1);

namespace LizardsAndPumpkins\Http;

use LizardsAndPumpkins\Http\ContentDelivery\GenericHttpResponse;

class PoweredByLizardsAndPumpkinsHttpResponseDecorator implements HttpResponse
{
    /**
     * @var HttpResponse
     */
    private $delegate;

    private function __construct(HttpResponse $delegate)
    {
        $this->delegate = $delegate;
    }
    
    public static function decorateHttpResponse(HttpResponse $delegate): HttpResponse
    {
        return new self($delegate);
    }

    public function getBody(): string
    {
        return $this->delegate->getBody();
    }

    public function getStatusCode(): int
    {
        return $this->delegate->getStatusCode();
    }

    public function send()
    {
        GenericHttpResponse::create(
            $this->getBody(),
            $this->getHeaders()->getAll(),
            $this->getStatusCode()
        )->send();
    }
    
    public function getHeaders(): HttpHeaders
    {
        return HttpHeaders::fromArray(array_merge(
            $this->delegate->getHeaders()->getAll(),
            ['X-Powered-By' => 'Lizards & Pumpkins']
        ));
    }
}
