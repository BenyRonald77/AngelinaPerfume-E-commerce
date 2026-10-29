<?php

namespace App\Support\Shopee;

class BrandMatcher
{
    /** @var array<string, string> alias (huruf kecil) => nama merek */
    private array $aliases = [];

    /**
     * @param  array<string, list<string>>  $brands  nama merek => alias yang dicari di nama listing
     */
    public function __construct(array $brands)
    {
        foreach ($brands as $name => $aliases) {
            foreach ([$name, ...$aliases] as $alias) {
                $this->aliases[mb_strtolower($alias)] = $name;
            }
        }

        // Alias terpanjang dicek lebih dulu: "SAFF & Co." sebelum "SAFF"
        uksort($this->aliases, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
    }

    public static function fromDataFile(string $path): self
    {
        return new self(require $path);
    }

    public function match(string $text): ?string
    {
        $haystack = mb_strtolower($text);

        foreach ($this->aliases as $alias => $name) {
            if (preg_match('/(^|[^\p{L}\p{N}])'.preg_quote($alias, '/').'($|[^\p{L}\p{N}])/u', $haystack)) {
                return $name;
            }
        }

        return null;
    }
}
