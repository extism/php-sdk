<?php

declare(strict_types=1);

namespace Extism;

use Extism\Manifest\ByteArrayWasmSource;

class Plugin
{
    private \Extism\Internal\LibExtism $lib;
    private \FFI\CData $handle;

    /**
     * Initialize a plugin from a byte array.
     *
     * @param string $bytes Wasm binary
     * @param bool $with_wasi Enable WASI
     * @param array $functions Array of host functions
     *
     */
    public static function fromBytes(string $bytes, bool $with_wasi = false, array $functions = []): self
    {
        $byteSource = new ByteArrayWasmSource($bytes);
        $manifest = new Manifest($byteSource);

        return new self($manifest, $with_wasi, $functions);
    }

    /**
     * Constructor
     *
     * @param Manifest $manifest A manifest that describes the Wasm binaries and configures permissions.
     * @param bool|PluginOptions $withWasiOrOptions Enable WASI or PluginOptions instance
     * @param array $functions Array of host functions
     */
    public function __construct(Manifest $manifest, $withWasiOrOptions = false, array $functions = [])
    {
        global $lib;

        if ($lib === null) {
            $lib = new \Extism\Internal\LibExtism();
        }

        $this->lib = $lib;
        
        // Handle backwards compatibility
        $options = $withWasiOrOptions;
        if (is_bool($withWasiOrOptions)) {
            $options = new PluginOptions($withWasiOrOptions);
        }

        $data = json_encode($manifest);
        if (!$data) {
            throw new \Extism\PluginLoadException("Failed to encode manifest");
        }

        $errPtr = $lib->ffi->new($lib->ffi->type("char*"));
 
        if ($options->getFuelLimit() !== null) {
            $handle = $this->lib->extism_plugin_new_with_fuel_limit(
                $data,
                strlen($data),
                $functions,
                count($functions),
                $options->getWithWasi(),
                $options->getFuelLimit(),
                \FFI::addr($errPtr)
            );
        } else {
            $handle = $this->lib->extism_plugin_new(
                $data,
                strlen($data),
                $functions,
                count($functions),
                $options->getWithWasi(),
                \FFI::addr($errPtr)
            );
        }

        if (\FFI::isNull($errPtr) === false) {
            $error = \FFI::string($errPtr);
            $this->lib->extism_plugin_new_error_free($errPtr);
            throw new \Extism\PluginLoadException("Extism: unable to load plugin: " . $error);
        }

        $this->handle = $handle;
    }

    /**
     * Internal constructor from CompiledPlugin
     * @internal
     */
    public static function fromCompiled(CompiledPlugin $compiled): self
    {
        global $lib;

        if ($lib === null) {
            $lib = new \Extism\Internal\LibExtism();
        }

        $errPtr = $lib->ffi->new($lib->ffi->type("char*"));
        $handle = $lib->extism_plugin_new_from_compiled($compiled->getNativeHandle(), \FFI::addr($errPtr));

        if (\FFI::isNull($errPtr) === false) {
            $error = \FFI::string($errPtr);
            $lib->extism_plugin_new_error_free($errPtr);
            throw new \Extism\PluginLoadException("Extism: unable to load plugin from compiled: " . $error);
        }

        $plugin = new self();
        $plugin->lib = $lib;
        $plugin->handle = $handle;

        return $plugin;
    }

    /**
     * Enable HTTP response headers in plugins using `extism:host/env::http_request`
     */
    public function allowHttpResponseHeaders(): void
    {
        $this->lib->extism_plugin_allow_http_response_headers($this->handle);
    }

    /**
     * Reset the Extism runtime, this will invalidate all allocated memory
     *
     * @return bool
     */
    public function reset(): bool
    {
        return $this->lib->extism_plugin_reset($this->handle);
    }

    /**
     * Update plugin config values.
     *
     * @param array $config New configuration values
     * @return bool
     */
    public function updateConfig(array $config): bool
    {
        $json = json_encode($config);
        if (!$json) {
            return false;
        }

        return $this->lib->extism_plugin_config($this->handle, $json, strlen($json));
    }

    /**
     * Get the plugin's ID.
     *
     * @return string UUID string
     */
    public function getId(): string
    {
        $bytes = $this->lib->extism_plugin_id($this->handle);
        return bin2hex(\FFI::string($bytes, 16));
    }

    /**
     * Check if the plugin contains a function.
     *
     * @param string $name
     *
     * @return bool `true` if the function exists, `false` otherwise
     */
    public function functionExists(string $name): bool
    {
        return $this->lib->extism_plugin_function_exists($this->handle, $name);
    }

    /**
     * Call a function in the Plugin and return the result.
     *
     * @param string $name Name of function.
     * @param string $input Input buffer
     *
     * @return string Output buffer
     */
    public function call(string $name, string $input = ""): string
    {
        $rc = $this->lib->extism_plugin_call($this->handle, $name, $input, strlen($input));

        $msg = "code = " . $rc;
        $err = $this->lib->extism_error($this->handle);
        if ($err) {
            $msg = $msg . ", error = " . $err;
            throw new \Extism\FunctionCallException("Extism: call to '" . $name . "' failed with " . $msg, $err, $name);
        }

        return $this->lib->extism_plugin_output_data($this->handle);
    }

    /**
     * Call a function with host context.
     *
     * @param string $name Name of function
     * @param string $input Input buffer
     * @param mixed $context Host context data
     * @return string Output buffer
     */
    public function callWithContext(string $name, string $input = "", $context = null): string
    {
        $rc = $this->lib->extism_plugin_call_with_host_context($this->handle, $name, $input, strlen($input), $context);

        $msg = "code = " . $rc;
        $err = $this->lib->extism_error($this->handle);
        if ($err) {
            $msg = $msg . ", error = " . $err;
            throw new \Extism\FunctionCallException("Extism: call to '" . $name . "' failed with " . $msg, $err, $name);
        }

        return $this->lib->extism_plugin_output_data($this->handle);
    }

    /**
     * Configures file logging. This applies to all Plugin instances.
     *
     * @param string $filename Path of log file. The file will be created if it doesn't exist.
     * @param string $level Minimum log level. Valid values are: `trace`, `debug`, `info`, `warn`, `error`
     * or more complex filter like `extism=trace,cranelift=debug`.
     */
    public static function setLogFile(string $filename, string $level): void
    {
        $lib = new \Extism\Internal\LibExtism();
        $lib->extism_log_file($filename, $level);
    }

    /**
     * Get the Extism version string
     * @return string
     */
    public static function version(): string
    {
        $lib = new \Extism\Internal\LibExtism();
        return $lib->extism_version();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->lib->extism_plugin_free($this->handle);
    }
}