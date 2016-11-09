<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\Import\Tax;

use LizardsAndPumpkins\Import\Price\Price;
use LizardsAndPumpkins\Import\Tax\Exception\InvalidTaxRateException;

class DemoProjectTaxRate implements TaxService
{
    /**
     * @var int
     */
    private $rate;

    private function __construct(int $rate)
    {
        $this->validateRate($rate);
        $this->rate = $rate;
    }

    public static function fromInt(int $rate) : DemoProjectTaxRate
    {
        return new self($rate);
    }

    private function validateRate(int $rate)
    {
        if (0 === $rate) {
            throw new InvalidTaxRateException('The tax rate must not be zero');
        }
    }

    public function getRate() : int
    {
        return (int) ($this->getFactor() * 100 - 100);
    }

    public function applyTo(Price $price) : Price
    {
        return $price->multiplyBy($this->getFactor());
    }

    final protected function getFactor() : float
    {
        return 1 + $this->rate / 100;
    }
}
