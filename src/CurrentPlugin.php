<?php
declare(strict_types=1);
namespace Extism;

require_once __DIR__ . "/LibExtism.php";

class CurrentPlugin
{
    private \FFI\CData $handle;
    private \LibExtism $lib;

    function __construct($lib, \FFI\CData $handle)
    {
        $this->handle = $handle;
        $this->lib = $lib;
    }

    function read_memory(int $offset) : string 
    {
        $ptr = $this->lib->extism_current_plugin_memory($this->handle);
        $ptr = $this->lib->ffi->cast("char *", $ptr);
        $ptr = $this->lib->ffi->cast("char *", $ptr + $offset);

        $length = $this->lib->extism_current_plugin_memory_length($this->handle, $offset);

        return \FFI::string($ptr, $length);
    }

    function allocate_memory(int $size) : int
    {
        return $this->lib->extism_current_plugin_memory_alloc($this->handle, $size);
    }

    function write_memory(string $data) : int
    {
        $offset = $this->allocate_memory(strlen($data));
        $this->fill_memory($offset, $data);
        return $offset;
    }

    function fill_memory(int $offset, string $data) : void
    {
        $ptr = $this->lib->extism_current_plugin_memory($this->handle);
        $ptr = $this->lib->ffi->cast("char *", $ptr);
        $ptr = $this->lib->ffi->cast("char *", $ptr + $offset);

        \FFI::memcpy($ptr, $data, strlen($data));
    }

    function free_memory(int $offset) : void
    {
        $this->lib->extism_current_plugin_memory_free($this->handle, $offset);
    }
}