<?php

namespace App\Support\Shopee;

final class ParsedVariation
{
    public function __construct(
        public readonly string $aroma,
        public readonly ?float $ml,
        public readonly ?string $note = null,
    ) {}
}
