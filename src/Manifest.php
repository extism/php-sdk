<?php

declare(strict_types=1);

namespace Extism;

use Extism\Manifest\MemoryOptions;

/**
 * The manifest is a description of your plugin and some of the runtime constraints to apply to it.
 * You can think of it as a blueprint to build your plugin.
 */
class Manifest
{
    /**
    * Create a manifest from one or more Wasm sources.
    *
    * @param \Extism\Manifest\WasmSource[] $wasm Array of `WasmSource`s.
    */
    public function __construct(...$wasm)
    {
        $this->wasm = $wasm;
        $this->allowed_hosts = [];
        $this->allowed_paths = new \stdClass();
        $this->config = new \stdClass();
        $this->memory = new MemoryOptions();
    }

    /**
     * List of Wasm sources. See `UrlWasmSource`, `PathWasmSource` and `ByteArrayWasmSource`.
     *
     * @var \Extism\Manifest\WasmSource[]
     */
    public $wasm = [];

    /**
     * Configures memory for the Wasm runtime.
     * Memory is described in units of pages (64KB) and represents contiguous chunks of addressable memory.
     *
     * @var MemoryOptions|null
     */
    public $memory;

    /**
     * List of host names the plugins can access. Examples: `*.example.com`, `www.something.com`, `www.*.com`
     *
     * @var array|null
     */
    public $allowed_hosts;

    /**
     * Map of directories that can be accessed by the plugins. Examples: `src=dest`, `\var\apps\123=\home`
     *
     * @var \object|null
     */
    public $allowed_paths;

    /**
     * Configurations available to the plugins. Examples: `key=value`, `secret=1234`
     *
     * @var \object|null
     */
    public $config;

    /**
     * Plugin call timeout in milliseconds.
     *
     * @var int|null
     */
    public $timeout_ms;
}
