<?php
declare(strict_types=1);
namespace Extism;

require_once __DIR__ . "/LibExtism.php";
require_once __DIR__ . "/Manifest.php";

class Plugin
{
    private \LibExtism $lib;
    private \FFI\CData $handle;

    public static function fromBytes(string $bytes, bool $with_wasi = false): self
    {
        $byteSource = new ByteArrayWasmSource(base64_encode($bytes));
        $manifest = new Manifest($byteSource);

        return new self($manifest, $with_wasi);
    }

    public function __construct(Manifest $manifest, bool $with_wasi = false)
    {
        global $lib;

        if ($lib == null) {
            $lib = new \LibExtism();
        }

        $this->lib = $lib;

        $data = json_encode($manifest);

        if (!$data) {
            echo "failed to encode manifest!" . PHP_EOL;
            var_dump($manifest);
            return;
        }

        $errPtr = \FFI::new(\FFI::type("char*"));
        $handle = $this->lib->extism_plugin_new($data, strlen($data), [], 0, $with_wasi, \FFI::addr($errPtr));

        if (\FFI::isNull($errPtr) == false) {
            $error = \FFI::string($errPtr);
            $this->lib->extism_plugin_new_error_free($errPtr);
            throw new \Exception("Extism: unable to load plugin: " . $error);
        }

        $this->handle = $handle;
    }

    public function __destruct()
    {
        $this->lib->extism_plugin_free($this->handle);
    }

    public function functionExists($name)
    {
        return $this->lib->extism_plugin_function_exists($this->handle, $name);
    }

    public function call($name, $input = null)
    {
        $rc = $this->lib->extism_plugin_call($this->handle, $name, $input, strlen($input));

        if ($rc != 0) {
            $msg = "code = " . $rc;
            $err = $this->lib->extism_error($this->handle);
            if ($err) {
                $msg = $msg . ", error = " . \FFI::string($err);
                \FFI::free($err);
            }
            throw new \Exception("Extism: call to '" . $name . "' failed with " . $msg);
        }

        return $this->lib->extism_plugin_output_data($this->handle);
    }

    static function setLogFile($filename, $level)
    {
        $lib = new \LibExtism();
        $lib->extism_log_file($filename, $level);
    }

    static function version()
    {
        $lib = new \LibExtism();
        return $lib->extism_version();
    }
}