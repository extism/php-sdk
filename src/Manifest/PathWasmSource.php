<?php

namespace Extism\Manifest;

/**
 * Wasm Source represented by a file referenced by a path.
 */
class PathWasmSource extends WasmSource
{
    /**
     * Constructor.
     *
     * @param string $path path to wasm plugin.
     * @param string|null $name
     * @param string|null $hash
     */
    public function __construct($path, $name = null, $hash = null)
    {
        $this->path = realpath($path);

        if (!$this->path) {
            throw new \Extism\PluginLoadException("Path not found: '" . $path . "'");
        }

        $this->name = $name ?? pathinfo($path, PATHINFO_FILENAME);
        $this->hash = $hash;
    }

    /**
     * Path to wasm plugin.
     *
     * @var string
     */
    public $path;
}
