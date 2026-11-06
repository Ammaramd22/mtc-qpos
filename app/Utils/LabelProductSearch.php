<?php

namespace App\Utils;

/**
 * Matching rules for Print Labels search.
 *
 * A typed number matches an assigned size, SKU, or barcode.
 * It does not match a product just because that number appears in the name.
 */
class LabelProductSearch
{
    /**
     * Suggestions shown at once. The query still searches the whole catalog.
     * This is only the size of the dropdown, not a product-list page.
     */
    public const SUGGESTION_CAP = 40;

    /**
     * @return array<int, string>
     */
    public static function tokens(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }
            $out[] = mb_substr($part, 0, 40);
            if (count($out) >= 12) {
                break;
            }
        }

        return $out;
    }

    public static function isNumericToken(string $token): bool
    {
        return preg_match('/^\d+$/u', trim($token)) === 1;
    }

    /**
     * Pure numbers are size, SKU, or barcode terms, not name fragments.
     */
    public static function tokenMayMatchName(string $token): bool
    {
        return ! self::isNumericToken($token);
    }

    public static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    /**
     * Comma-separated product text such as "54, 56, 58".
     * "54" matches. "154" and "540" do not.
     *
     * @param  mixed  $stored
     */
    public static function listHasExactToken($stored, string $token): bool
    {
        $needle = mb_strtolower(trim($token));
        if ($needle === '') {
            return false;
        }

        foreach (ProductSizeColor::splitList($stored) as $part) {
            if (mb_strtolower($part) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * "Whi" matches "White". "white" does not match "offwhite".
     *
     * @param  mixed  $stored
     */
    public static function listHasPrefixToken($stored, string $token): bool
    {
        $needle = mb_strtolower(trim($token));
        if ($needle === '') {
            return false;
        }

        foreach (ProductSizeColor::splitList($stored) as $part) {
            if (mb_stripos($part, $needle) === 0 || mb_strtolower($part) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Catalog assignments win. The text column is the copy of those assignments.
     * Variation values are used only when the product has no catalog size or color.
     *
     * @param  array<int, string>  $catalog
     * @param  mixed  $legacyText
     * @param  array<int, string>  $variationNames
     * @return array{values: array<int, string>, source: string}
     */
    public static function resolveAssigned(array $catalog, $legacyText, array $variationNames): array
    {
        $fromCatalog = ProductSizeColor::optionList($catalog, '');
        if ($fromCatalog !== []) {
            return ['values' => $fromCatalog, 'source' => 'catalog'];
        }

        $fromText = ProductSizeColor::splitList($legacyText);
        if ($fromText !== []) {
            return ['values' => $fromText, 'source' => 'text'];
        }

        $fromVariations = ProductSizeColor::uniqueNames($variationNames);
        if ($fromVariations !== []) {
            return ['values' => $fromVariations, 'source' => 'variation'];
        }

        return ['values' => [], 'source' => 'none'];
    }

    /**
     * @param  array<int, string>  $options
     * @param  array<int, string>  $tokens
     */
    public static function matchedValue(array $options, array $tokens, bool $allowPrefix): ?string
    {
        $exact = null;
        foreach ($tokens as $token) {
            $needle = mb_strtolower(trim($token));
            if ($needle === '') {
                continue;
            }
            foreach ($options as $option) {
                if (mb_strtolower($option) === $needle) {
                    $exact = $option;
                }
            }
        }
        if ($exact !== null) {
            return $exact;
        }
        if (! $allowPrefix) {
            return null;
        }

        $prefixHit = null;
        foreach ($tokens as $token) {
            if (self::isNumericToken($token)) {
                continue;
            }
            $needle = mb_strtolower(trim($token));
            if ($needle === '') {
                continue;
            }
            $hits = [];
            foreach ($options as $option) {
                if (mb_stripos($option, $needle) === 0) {
                    $hits[] = $option;
                }
            }
            if (count($hits) === 1) {
                $prefixHit = $hits[0];
            }
        }

        return $prefixHit;
    }
}
