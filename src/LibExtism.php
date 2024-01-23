<?php

class LibExtism
{
    private FFI $extism;

    public function __construct() {
        $name = LibExtism::soname();
        $this->extism = self::findSo($name);
    }

    function findSo(string $name): FFI {
        $platform = php_uname("s");

        $directories = [];
        if (self::startsWith($platform, "windows")) {
            $path = getenv('PATH');
            $directories = explode(PATH_SEPARATOR, $path);

        } else {
            $directories = ['/usr/local/lib', '/usr/lib'];
        }

        $searchedPaths = [];
        foreach ($directories as $directory) {
            $fullPath = $directory . DIRECTORY_SEPARATOR . $name;

            if (file_exists($fullPath)) {
                return FFI::cdef(
                    file_get_contents(__DIR__ . "/extism.h"),
                    $fullPath
                );
            }

            array_push($searchedPaths, $fullPath);
        }

        throw new \RuntimeException('Failed to find shared object. Searched locations: ' . implode(', ', $searchedPaths));
    }

    function soname() {
        $platform = php_uname("s");
        switch ($platform) {
            case "Darwin":
                return "libextism.dylib";
            case "Linux": 
                return "libextism.so";
            case "Windows NT":
                return "extism.dll";
            default:
                throw new \Exception("Extism: unsupported platform ".$platform);
        }
    }

    function extism_plugin_new(string $wasm, int $wasm_size, array $functions, int $n_functions, bool $with_wasi, ?FFI\CData $errmsg): FFI\CData|null
    {
        $ptr = $this->owned("uint8_t", $wasm);
        $wasi = $with_wasi ? 1 : 0;
        $pluginPtr = $this->extism->extism_plugin_new($ptr, $wasm_size, null, $n_functions, $wasi, $errmsg);

        return $this->extism->cast("ExtismPlugin*", $pluginPtr);
    }

    function extism_plugin_new_error_free(FFI\CData $ptr): void {
        $this->extism->extism_plugin_new_error_free($ptr);
    }

    function extism_plugin_function_exists(FFI\CData $plugin, string $func_name): bool
    {
        return $this->extism->extism_plugin_function_exists($plugin, $func_name);
    }

    function extism_version(): string
    {
        return $this->extism->extism_version();
    }

    function extism_plugin_call(FFI\CData $plugin, string $func_name, string $data, int $data_len): int
    {
        $dataPtr = $this->owned("uint8_t", $data);
        return $this->extism->extism_plugin_call($plugin, $func_name, $dataPtr, $data_len);
    }

    function extism_error(FFI\CData $plugin): ?string
    {
        return $this->extism->extism_error($plugin);
    }

    function extism_plugin_error(FFI\CData $plugin): ?string
    {
        return $this->extism->extism_plugin_error($plugin);
    }

    function extism_plugin_output_data(FFI\CData $plugin): string
    {
        $length = $this->extism->extism_plugin_output_length($plugin);

        $ptr = $this->extism->extism_plugin_output_data($plugin);

        return FFI::string($ptr, $length);
    }

    function extism_plugin_free(FFI\CData $plugin): void
    {
        $this->extism->extism_plugin_free($plugin);
    }

    function extism_log_file(string $filename, string $log_level): void
    {
        $filenamePtr = $this->ownedZero($filename);
        $log_levelPtr = $this->ownedZero($log_level);

        $this->extism->extism_log_file($filenamePtr, $log_levelPtr);
    }

    function owned(string $type, string $string): FFI\CData|null
    {
        if (strlen($string) == 0) {
            return null;
        }

        $str = $this->extism->new($type . "[" . \strlen($string) . "]", true);
        FFI::memcpy($str, $string, \strlen($string));
        return $str;
    }

    function ownedZero(string $string): FFI\CData|null
    {
        return self::owned("char", "$string\0");
    }

    function startsWith($haystack, $needle) {
        return strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0;
    }
}

?>