<?php

namespace Extism\Internal;

// phpcs:ignore
class LibExtism
{
    public \FFI $ffi;

    public function __construct()
    {
        $name = LibExtism::soname();
        $this->ffi = LibExtism::findSo($name);
    }

    private function findSo(string $name): \FFI
    {
        $platform = php_uname("s");
        $directories = [];
        if ($this->startsWith($platform, "windows")) {
            $path = getenv('PATH');
            $directories = explode(PATH_SEPARATOR, $path);
        } else {
            $directories = ['/usr/local/lib', '/usr/lib'];
        }

        $searchedPaths = [];
        foreach ($directories as $directory) {
            $fullPath = $directory . DIRECTORY_SEPARATOR . $name;

            if (file_exists($fullPath)) {
                return \FFI::cdef(
                    file_get_contents(__DIR__ . "/extism.h"),
                    $fullPath
                );
            }

            array_push($searchedPaths, $fullPath);
        }

        throw new \RuntimeException('Failed to find shared object. Searched locations: ' . implode(', ', $searchedPaths));
    }

    private function soname()
    {
        $platform = php_uname("s");
        switch ($platform) {
            case "Darwin":
                return "libextism.dylib";
            case "Linux":
                return "libextism.so";
            case "Windows NT":
                return "extism.dll";
            default:
                throw new \RuntimeException("Extism: unsupported platform " . $platform);
        }
    }

    public function extism_current_plugin_memory(\FFI\CData $plugin): \FFI\CData
    {
        return $this->ffi->extism_current_plugin_memory($plugin);
    }

    public function extism_current_plugin_memory_free(\FFI\CData $plugin, \FFI\CData $ptr): void
    {
        $this->ffi->extism_current_plugin_memory_free($plugin, $ptr);
    }

    public function extism_current_plugin_memory_alloc(\FFI\CData $plugin, int $size): int
    {
        return $this->ffi->extism_current_plugin_memory_alloc($plugin, $size);
    }

    public function extism_current_plugin_memory_length(\FFI\CData $plugin, int $offset): int
    {
        return $this->ffi->extism_current_plugin_memory_length($plugin, $offset);
    }

    public function extism_plugin_new(string $wasm, int $wasm_size, array $functions, int $n_functions, bool $with_wasi, ?\FFI\CData $errmsg): ?\FFI\CData
    {
        $functionHandles = array_map(function ($function) {
            return $function->handle;
        }, $functions);

        $functionHandles = $this->toCArray($functionHandles, "ExtismFunction*");

        $ptr = $this->owned("uint8_t", $wasm);
        $wasi = $with_wasi ? 1 : 0;
        $pluginPtr = $this->ffi->extism_plugin_new($ptr, $wasm_size, $functionHandles, $n_functions, $wasi, $errmsg);

        return $this->ffi->cast("ExtismPlugin*", $pluginPtr);
    }

    public function extism_plugin_new_error_free(\FFI\CData $ptr): void
    {
        $this->ffi->extism_plugin_new_error_free($ptr);
    }

    public function extism_plugin_function_exists(\FFI\CData $plugin, string $func_name): bool
    {
        return $this->ffi->extism_plugin_function_exists($plugin, $func_name);
    }

    public function extism_version(): string
    {
        return $this->ffi->extism_version();
    }

    public function extism_plugin_call(\FFI\CData $plugin, string $func_name, string $data, int $data_len): int
    {
        $dataPtr = $this->owned("uint8_t", $data);
        return $this->ffi->extism_plugin_call($plugin, $func_name, $dataPtr, $data_len);
    }

    public function extism_error(\FFI\CData $plugin): ?string
    {
        return $this->ffi->extism_error($plugin);
    }

    private function extism_plugin_error(\FFI\CData $plugin): ?string
    {
        return $this->ffi->extism_plugin_error($plugin);
    }

    public function extism_plugin_output_data(\FFI\CData $plugin): string
    {
        $length = $this->ffi->extism_plugin_output_length($plugin);

        $ptr = $this->ffi->extism_plugin_output_data($plugin);

        return \FFI::string($ptr, $length);
    }

    public function extism_plugin_free(\FFI\CData $plugin): void
    {
        $this->ffi->extism_plugin_free($plugin);
    }

    public function extism_log_file(string $filename, string $log_level): void
    {
        $filenamePtr = $this->ownedZero($filename);
        $log_levelPtr = $this->ownedZero($log_level);

        $this->ffi->extism_log_file($filenamePtr, $log_levelPtr);
    }

    public function extism_function_new(string $name, array $inputTypes, array $outputTypes, callable $callback, $userData, $freeUserData): \FFI\CData
    {
        $inputs = $this->toCArray($inputTypes, "ExtismValType");
        $outputs = $this->toCArray($outputTypes, "ExtismValType");

        $handle = $this->ffi->extism_function_new($name, $inputs, count($inputTypes), $outputs, count($outputTypes), $callback, $userData, $freeUserData);

        return $handle;
    }

    public function extism_function_free(\FFI\CData $handle): void
    {
        $this->ffi->extism_function_free($handle);
    }

    public function extism_function_set_namespace(\FFI\CData $handle, string $name)
    {
        $namePtr = $this->ownedZero($name);
        $this->ffi->extism_function_set_namespace($handle, $namePtr);
    }

    private function toCArray(array $array, string $type): ?\FFI\CData
    {
        if (count($array) == 0) {
            return $this->ffi->new($type . "*");
        }

        $cArray = $this->ffi->new($type . "[" . count($array) . "]");
        for ($i = 0; $i < count($array); $i++) {
            $cArray[$i] = $array[$i];
        }

        return $cArray;
    }

    private function owned(string $type, string $string): ?\FFI\CData
    {
        if (strlen($string) == 0) {
            return null;
        }

        $str = $this->ffi->new($type . "[" . \strlen($string) . "]", true);
        \FFI::memcpy($str, $string, \strlen($string));
        return $str;
    }

    private function ownedZero(string $string): ?\FFI\CData
    {
        return $this->owned("char", "$string\0");
    }

    private function startsWith($haystack, $needle)
    {
        return strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0;
    }
}
