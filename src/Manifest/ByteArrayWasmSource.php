<?php

namespace Extism\Manifest;

/**
 * Wasm Source represented by raw bytes.
 */
class ByteArrayWasmSource extends WasmSource
{
    /**
     * Constructor.
     *
     * @param string $data the byte array representing the Wasm code
     * @param string|null $name
     * @param string|null $hash
     */
    public function __construct(string $data, $name = null, $hash = null)
    {
        $this->data = base64_encode($data);
        $this->name = $name;
        $this->hash = $hash;
    }

    /**
     * The byte array representing the Wasm code encoded in Base64.
     *
     * @var string
     */
    public $data;
}
