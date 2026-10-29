<?php

namespace App\Shipping;

final class ShippingOption
{
    public function __construct(
        public readonly string $key,
        public readonly string $courier,
        public readonly ?string $service,
        public readonly int $cost,
        public readonly ?string $estimate,
    ) {}

    public function label(): string
    {
        return trim($this->courier.($this->service ? ' '.$this->service : ''));
    }
}
