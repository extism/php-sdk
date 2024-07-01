<?php

namespace Extism\Manifest;

/**
 * Wasm Source represented by a file referenced by a path.
 */
class UrlWasmSource extends WasmSource
{
    /**
     * Constructor.
     *
     * @param string $url uri to wasm plugin.
     * @param string|null $name
     * @param string|null $hash
     */
    public function __construct($url, $name = null, $hash = null)
    {
        $this->url = $url;
        $this->name = $name;
        $this->hash = $hash;
        $this->headers = new \stdClass();
    }

    /**
     * Uri to wasm plugin.
     *
     * @var string
     */
    public $url;

    /**
     * HTTP headers. Examples: `header1=value`, `Authorization=Basic 123`
     *
     * @var object|null
     */
    public $headers;

    /**
     * HTTP Method. Examples: `GET`, `POST`, `DELETE`. See `HttpMethod` for a list of options.
     *
     * @var string|null
     */
    public $method;
}
