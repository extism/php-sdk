<?php
declare(strict_types=1);
namespace Extism;

/**
 * The manifest is a description of your plugin and some of the runtime constraints to apply to it.
 * You can think of it as a blueprint to build your plugin.
 */
class Manifest
{
     /**
     * Create a manifest from one or more Wasm sources.
     *
     * @param WasmSource[] $wasm Array of `WasmSource`s.
     */
    public function __construct(...$wasm)
    {
        $this->wasm = $wasm;
        $this->allowed_hosts = [];
        $this->allowed_paths = new \stdClass();
        $this->config = new \stdClass();
        $this->memory = new MemoryOptions();
    }

    /**
     * List of Wasm sources. See `UrlWasmSource`, `PathWasmSource` and `ByteArrayWasmSource`.
     *
     * @var WasmSource[]
     */
    public $wasm = [];

    /**
     * Configures memory for the Wasm runtime.
     * Memory is described in units of pages (64KB) and represents contiguous chunks of addressable memory.
     *
     * @var MemoryOptions|null
     */
    public $memory;

    /**
     * List of host names the plugins can access. Examples: `*.example.com`, `www.something.com`, `www.*.com`
     *
     * @var array|null
     */
    public $allowed_hosts;

    /**
     * Map of directories that can be accessed by the plugins. Examples: `src=dest`, `\var\apps\123=\home`
     *
     * @var \object|null
     */
    public $allowed_paths;

    /**
     * Configurations available to the plugins. Examples: `key=value`, `secret=1234`
     *
     * @var \object|null
     */
    public $config;

    /**
     * Plugin call timeout in milliseconds.
     *
     * @var int|null
     */
    public $timeout_ms;
}

/**
 * Configures memory for the Wasm runtime.
 * Memory is described in units of pages (64KB) and represents contiguous chunks of addressable memory.
 */
class MemoryOptions
{
    /**
     * Max number of pages. Each page is 64KB.
     *
     * @var int
     */
    public $max_pages;

    /**
     * Max number of bytes returned by `extism_http_request`
     *
     * @var int
     */
    public $max_http_response_bytes;

    /**
     * Max number of bytes allowed in the Extism var store
     *
     * @var int
     */
    public $max_var_bytes;
}

/**
 * A named Wasm source.
 */
abstract class WasmSource
{
    /**
     * Logical name of the Wasm source.
     *
     * @var string|null
     */
    public $name;

    /**
     * Hash of the WASM source.
     *
     * @var string|null
     */
    public $hash;
}

/**
 * Wasm Source represented by a file referenced by a path.
 */
class PathWasmSource extends WasmSource
{
    /**
     * Constructor.
     *
     * @param string $path path to wasm plugin.
     * @param string|null $name
     * @param string|null $hash
     */
    public function __construct($path, $name = null, $hash = null)
    {
        $this->path = realpath($path);

        if (!$this->path) {
            throw new \Exception("Path not found: '" . $path . "'");
        }

        $this->name = $name ?? pathinfo($path, PATHINFO_FILENAME);
        $this->hash = $hash;
    }

    /**
     * Path to wasm plugin.
     *
     * @var string
     */
    public $path;
}

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

/**
 * HTTP defines a set of request methods to indicate the desired action to be performed for a given resource.
 */
class HttpMethod
{
    const GET = 'GET';
    const HEAD = 'HEAD';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const CONNECT = 'CONNECT';
    const OPTIONS = 'OPTIONS';
    const TRACE = 'TRACE';
    const PATCH = 'PATCH';
}

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

?>
