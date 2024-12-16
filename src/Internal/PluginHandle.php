<?php

namespace Extism\Internal;

/** @internal */
class PluginHandle
{
    /** @internal */
    public \Extism\Internal\LibExtism $lib;
    /** @internal */
    public \FFI\CData $native;

    public function __construct($lib, \FFI\CData $handle)
    {
        $this->native = $handle;
        $this->lib = $lib;
    }
}