<?php

namespace Extism\Manifest;

/**
 * A named Wasm source.
 */
abstract class WasmSource
{
    /**
     * Logical name of the Wasm source.
     *
     * @var string|null
     */
    public $name;

    /**
     * Hash of the WASM source.
     *
     * @var string|null
     */
    public $hash;
}
