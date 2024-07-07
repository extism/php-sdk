<?php

declare(strict_types=1);

namespace Extism\Manifest;

/**
 * Configures memory for the Wasm runtime.
 * Memory is described in units of pages (64KB) and represents contiguous chunks of addressable memory.
 */
class MemoryOptions
{
    /**
     * Max number of pages. Each page is 64KB.
     *
     * @var int
     */
    public $max_pages;

    /**
     * Max number of bytes returned by `extism_http_request`
     *
     * @var int
     */
    public $max_http_response_bytes;

    /**
     * Max number of bytes allowed in the Extism var store
     *
     * @var int
     */
    public $max_var_bytes;
}
