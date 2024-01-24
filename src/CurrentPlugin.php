<?php
declare(strict_types=1);
namespace Extism;

require_once __DIR__ . "/LibExtism.php";

/**
 * Represents a plugin that is calling the currently running host function.
 */
class CurrentPlugin
{
    private \FFI\CData $handle;
    private \LibExtism $lib;

    /**
     * constructor.
     * 
     * @param \LibExtism $lib
     * @param \FFI\CData $handle
     */
    function __construct($lib, \FFI\CData $handle)
    {
        $this->handle = $handle;
        $this->lib = $lib;
    }

    /**
     * Reads a string from the plugin's memory at the given offset.
     * 
     * @param int $offset Offset of the block to read.
     */
    function read_block(int $offset) : string 
    {
        $ptr = $this->lib->extism_current_plugin_memory($this->handle);
        $ptr = $this->lib->ffi->cast("char *", $ptr);
        $ptr = $this->lib->ffi->cast("char *", $ptr + $offset);

        $length = $this->lib->extism_current_plugin_memory_length($this->handle, $offset);

        return \FFI::string($ptr, $length);
    }

    /**
     * Allocates a block of memory in the plugin's memory and returns the offset.
     * 
     * @param int $size Size of the block to allocate in bytes.
     */
    function allocate_block(int $size) : int
    {
        return $this->lib->extism_current_plugin_memory_alloc($this->handle, $size);
    }

    /**
     * Writes a string to the plugin's memory, returning the offset of the block.
     * 
     * @param string $data Buffer to write to the plugin's memory.
     */
    function write_block(string $data) : int
    {
        $offset = $this->allocate_block(strlen($data));
        $this->fill_block($offset, $data);
        return $offset;
    }

    /**
     * Fills a block of memory in the plugin's memory.
     * 
     * @param int $offset Offset of the block to fill.
     * @param string $data Buffer to fill the block with.
     */
    function fill_block(int $offset, string $data) : void
    {
        $ptr = $this->lib->extism_current_plugin_memory($this->handle);
        $ptr = $this->lib->ffi->cast("char *", $ptr);
        $ptr = $this->lib->ffi->cast("char *", $ptr + $offset);

        \FFI::memcpy($ptr, $data, strlen($data));
    }

    /**
     * Frees a block of memory in the plugin's memory.
     * 
     * @param int $offset Offset of the block to free.
     */
    function free_block(int $offset) : void
    {
        $this->lib->extism_current_plugin_memory_free($this->handle, $offset);
    }
}