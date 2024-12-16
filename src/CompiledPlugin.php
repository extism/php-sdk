<?php

declare(strict_types=1);

namespace Extism;

/**
 * A pre-compiled plugin ready to be instantiated.
 */
class CompiledPlugin
{
    private \FFI\CData $handle;
    private \Extism\Internal\LibExtism $lib;
    private array $functions;

    /**
     * Compile a plugin from a Manifest.
     *
     * @param Manifest $manifest A manifest that describes the Wasm binaries
     * @param array $functions Array of host functions
     * @param bool $withWasi Enable WASI support
     */
    public function __construct(Manifest $manifest, array $functions = [], bool $withWasi = false)
    {
        global $lib;

        if ($lib === null) {
            $lib = new \Extism\Internal\LibExtism();
        }

        $this->lib = $lib;
        $this->functions = $functions;

        $data = json_encode($manifest);
        if (!$data) {
            throw new \Extism\PluginLoadException("Failed to encode manifest");
        }

        $errPtr = $lib->ffi->new($lib->ffi->type("char*"));

        $handle = $this->lib->extism_compiled_plugin_new(
            $data, 
            strlen($data), 
            $functions, 
            count($functions), 
            $withWasi, 
            \FFI::addr($errPtr)
        );

        if (\FFI::isNull($errPtr) === false) {
            $error = \FFI::string($errPtr);
            $this->lib->extism_plugin_new_error_free($errPtr);
            throw new \Extism\PluginLoadException("Extism: unable to compile plugin: " . $error);
        }

        $this->handle = $handle;
    }

    /**
     * Instantiate a plugin from this compiled plugin.
     *
     * @return Plugin
     */
    public function instantiate(): Plugin
    {
        $errPtr = $this->lib->ffi->new($this->lib->ffi->type("char*"));
        $nativeHandle = $this->lib->extism_plugin_new_from_compiled($this->handle, \FFI::addr($errPtr));

        if (\FFI::isNull($errPtr) === false) {
            $error = \FFI::string($errPtr);
            $this->lib->extism_plugin_new_error_free($errPtr);
            throw new \Extism\PluginLoadException("Extism: unable to load plugin from compiled: " . $error);
        }

        $handle = new \Extism\Internal\PluginHandle($this->lib, $nativeHandle);

        return new Plugin($handle);
    }

    /**
     * Get the functions array
     * @internal
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * Destructor to clean up resources
     */
    public function __destruct()
    {
        $this->lib->extism_compiled_plugin_free($this->handle);
    }
}