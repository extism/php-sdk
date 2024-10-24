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
     * @param bool $with_wasi Enable WASI
     * @param array $functions Array of host functions
     */
    public function __construct(Manifest $manifest, bool $with_wasi = false, array $functions = [])
    {
        global $lib;

        if ($lib == null) {
            $lib = new \Extism\Internal\LibExtism();
        }

        $this->lib = $lib;
        $data = json_encode($manifest);

        if (!$data) {
            echo "failed to encode manifest!" . PHP_EOL;
            var_dump($manifest);
            return;
        }

        $errPtr = $lib->ffi->new($lib->ffi->type("char*"));
        $handle = $this->lib->extism_plugin_new($data, strlen($data), $functions, count($functions), $with_wasi, \FFI::addr($errPtr));

        if (\FFI::isNull($errPtr) == false) {
            $error = \FFI::string($errPtr);
            $this->lib->extism_plugin_new_error_free($errPtr);
            throw new \Extism\PluginLoadException("Extism: unable to load plugin: " . $error);
        }

        $this->handle = $handle;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->lib->extism_plugin_free($this->handle);
    }

    /**
     * Check if the plugin contains a function.
     *
     * @param string $name
     *
     * @return bool `true` if the function exists, `false` otherwise
     */
    public function functionExists(string $name)
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
    public function call(string $name, string $input = null): string
    {
        if ($input == null) {
            $input = "";
        }

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
    public static function version()
    {
        $lib = new \Extism\Internal\LibExtism();
        return $lib->extism_version();
    }
}
