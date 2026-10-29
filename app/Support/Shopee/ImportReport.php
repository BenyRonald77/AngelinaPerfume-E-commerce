<?php

namespace App\Support\Shopee;

final class ImportReport
{
    public int $productsCreated = 0;

    public int $productsUpdated = 0;

    public int $aromas = 0;

    public int $variants = 0;

    public int $variantsDeactivated = 0;

    public int $imagesConverted = 0;

    public int $imagesSkipped = 0;

    /** @var list<string> hal yang perlu dicek admin */
    public array $warnings = [];

    /** @var list<string> keterangan yang tidak perlu ditindaklanjuti */
    public array $notes = [];

    public function warn(string $folder, string $message): void
    {
        $this->warnings[] = "{$folder}: {$message}";
    }

    public function note(string $folder, string $message): void
    {
        $this->notes[] = "{$folder}: {$message}";
    }
}
