<?php

namespace App\Support\Shopee;

final class ParsedName
{
    /**
     * @param  list<float>  $sizesMl  ukuran yang tertulis di nama listing
     */
    public function __construct(
        public readonly string $category,
        public readonly string $name,
        public readonly ?string $concentration,
        public readonly array $sizesMl,
        public readonly ?string $packagingNote,
    ) {}
}
