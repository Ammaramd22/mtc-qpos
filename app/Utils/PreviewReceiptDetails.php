<?php

namespace App\Utils;

/**
 * Receipt object for the layout editor preview.
 * Missing fields read as empty so invoice blades can skip them.
 */
class PreviewReceiptDetails
{
    /** @var array<string, mixed> */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __get($key)
    {
        return $this->data[$key] ?? null;
    }

    public function __isset($key)
    {
        if (! array_key_exists($key, $this->data)) {
            return false;
        }
        $value = $this->data[$key];

        return $value !== null && $value !== '';
    }
}
