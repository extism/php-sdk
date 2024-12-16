<?php

declare(strict_types=1);

namespace Extism;

/**
 * Represents a plugin that is calling the currently running host function.
 */
class CurrentPlugin
{
    private \FFI\CData $handle;
    private \Extism\Internal\LibExtism $lib;

    /**
     * constructor.
     *
     * @param \Extism\Internal\LibExtism $lib
     * @param \FFI\CData $handle
     */
    public function __construct($lib, \FFI\CData $handle)
    {
        $this->handle = $handle;
        $this->lib = $lib;
    }

   /**
     * Get *a copy* of the current plugin call's associated host context data. Returns null if call was made without host context.
     *
     * @return mixed|null Returns a copy of the host context data or null if none was provided
     */
    public function getCallHostContext()
    {
        $serialized = $this->lib->extism_current_plugin_host_context($this->handle);
        if ($serialized === null) {
            return null;
        }

        return unserialize($serialized);
    }

    /**
     * Reads a string from the plugin's memory at the given offset.
     *
     * @param int $offset Offset of the block to read.
     */
    public function read_block(int $offset): string
    {
        $ptr = $this->lib->extism_current_plugin_memory($this->handle);
        $ptr = $this->lib->ffi->cast("char *", $ptr);
        $blockStart = $ptr + $offset;
        $ptr = $this->lib->ffi->cast("char *", $blockStart);

        $length = $this->lib->extism_current_plugin_memory_length($this->handle, $offset);

        return \FFI::string($ptr, $length);
    }

    /**
     * Allocates a block of memory in the plugin's memory and returns the offset.
     *
     * @param int $size Size of the block to allocate in bytes.
     */
    private function allocate_block(int $size): int
    {
        return $this->lib->extism_current_plugin_memory_alloc($this->handle, $size);
    }

    /**
     * Writes a string to the plugin's memory, returning the offset of the block.
     *
     * @param string $data Buffer to write to the plugin's memory.
     */
    public function write_block(string $data): int
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
    private function fill_block(int $offset, string $data): void
    {
        $ptr = $this->lib->extism_current_plugin_memory($this->handle);
        $ptr = $this->lib->ffi->cast("char *", $ptr);
        $blockStart = $ptr + $offset;
        $ptr = $this->lib->ffi->cast("char *", $blockStart);

        \FFI::memcpy($ptr, $data, strlen($data));
    }

    /**
     * Frees a block of memory in the plugin's memory.
     *
     * @param int $offset Offset of the block to free.
     */
    private function free_block(int $offset): void
    {
        $this->lib->extism_current_plugin_memory_free($this->handle, $offset);
    }
}
