<?php

namespace Extism\Internal;

class PluginHandle
{
    public \Extism\Internal\LibExtism $lib;
    public \FFI\CData $handle;

    public function __construct($lib, \FFI\CData $handle)
    {
        $this->handle = $handle;
        $this->lib = $lib;
    }
}