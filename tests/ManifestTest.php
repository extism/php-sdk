<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Extism\Plugin;
use Extism\Manifest;
use Extism\ByteArrayWasmSource;
use Extism\PathWasmSource;
use Extism\UrlWasmSource;

final class ManifestTest extends TestCase
{
    public function testCanLoadPluginFromByteArray(): void
    {
        $bytes = file_get_contents(__DIR__ . "/../wasm/hello.wasm");
        $wasm = new ByteArrayWasmSource($bytes, "main");
        $manifest = new Manifest($wasm);

        $plugin = new Plugin($manifest, true, []);

        $actual = $plugin->call("run_test", "");
        $this->assertEquals("Hello, world!", $actual);
    }

    public function testCanLoadPluginFromPath(): void
    {
        $wasm = new PathWasmSource(__DIR__ . "/../wasm/hello.wasm");
        $manifest = new Manifest($wasm);

        $plugin = new Plugin($manifest, true, []);

        $actual = $plugin->call("run_test", "");
        $this->assertEquals("Hello, world!", $actual);
    }

    public function testCanLoadPluginFromUrl(): void
    {
        $wasm = new UrlWasmSource("https://github.com/extism/plugins/releases/download/v0.5.0/count_vowels.wasm");
        $manifest = new Manifest($wasm);

        $plugin = new Plugin($manifest, true, []);

        $response = $plugin->call("count_vowels", "Hello World!");
        $actual = json_decode($response);

        $this->assertEquals(3, $actual->count);
    }

    public function testCanSetConfig(): void
    {
        $plugin = self::loadPlugin("config.wasm", function ($manifest) {
            $manifest->config->thing = "hello";
        });

        $actual = $plugin->call("run_test", "");
        $this->assertEquals("{\"config\": \"hello\"}", $actual);
    }

    public function testCanLeaveConfigUnset(): void
    {
        $plugin = self::loadPlugin("config.wasm");

        $actual = $plugin->call("run_test", "");
        $this->assertEquals("{\"config\": \"<unset by host>\"}", $actual);
    }

    public function testCanMakeHttpCallsWhenAllowed(): void
    {
        $plugin = self::loadPlugin("http.wasm", function($manifest) {
            $manifest->allowed_hosts = ["jsonplaceholder.*.com"];
        });

        $response = $plugin->call("run_test", "");
        $actual = json_decode($response);
        $this->assertEquals(1, $actual->userId);
    }

    public function testCantMakeHttpCallsWhenDenied(): void
    {
        $this->expectException(\Exception::class);

        $plugin = self::loadPlugin("http.wasm", function ($manifest) {
            $manifest->allowed_hosts = [];
        });

        $plugin->call("run_test", "");
    }

    public static function loadPlugin(string $name, ?callable $config = null)
    {
        $path = __DIR__ . '/../wasm/' . $name;
        $manifest = new Manifest(new PathWasmSource($path, 'main'));
    
        if ($config !== null) {
            $config($manifest);
        }
    
        return new Plugin($manifest, true, []);
    }
}

?>