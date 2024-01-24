<?php declare(strict_types=1);

use Extism\HostFunction;
use PHPUnit\Framework\TestCase;
use Extism\Plugin;
use Extism\Manifest;
use Extism\ByteArrayWasmSource;
use Extism\PathWasmSource;
use Extism\UrlWasmSource;
use Extism\ExtismValType;

final class PluginTest extends TestCase
{
    public function testAlloc(): void
    {
        $plugin = self::loadPlugin("alloc.wasm", []);

        $response = $plugin->call("run_test", "");
        $this->assertEquals("", $response);
    }

    public function testFail(): void
    {
        $this->expectException(\Exception::class);

        $plugin = self::loadPlugin("fail.wasm", []);

        $plugin->call("run_test", "");
    }

    public function testExit(): void
    {
        $plugin = self::loadPlugin("exit.wasm", [], function ($manifest) {
            $manifest->config->code = "2";
        });

        try {
            $plugin->call("_start", "");
        } catch (\Exception $e) {
            $this->assertStringContainsString("2", $e->getMessage());
        }
    }

    public function testTimeout(): void
    {
        $plugin = self::loadPlugin("sleep.wasm", [], function ($manifest) {
            $manifest->timeout_ms = 50;
            $manifest->config->duration = "3"; // sleep for 3 seconds
        });

        try {
            $plugin->call("run_test", "");
        } catch (\Exception $e) {
            $this->assertStringContainsString("timeout", $e->getMessage());
        }
    }

    public function testFileSystem(): void
    {
        $plugin = self::loadPlugin("fs.wasm", [], function ($manifest) {
            $manifest->allowed_paths = ["tests/data" => "/mnt"];
        });

        $response = $plugin->call("run_test", "");
        $this->assertEquals("hello world!", $response);
    }

    public function testFunctionExists(): void
    {
        $plugin = self::loadPlugin("alloc.wasm", []);

        $this->assertTrue($plugin->functionExists("run_test"));
        $this->assertFalse($plugin->functionExists("i_dont_exist"));
    }

    public function testHostFunctions(): void
    {
        $kvstore = [];

        $kvRead = new HostFunction("kv_read", [ExtismValType::I64], [ExtismValType::I64], function (string $key) use ($kvstore): string {
            return $kvstore[$key] ?? "\0\0\0\0";
        });

        $kvWrite = new HostFunction("kv_write", [ExtismValType::I64, ExtismValType::I64], [], function (string $key, string $value) use ($kvstore): void {
            $kvstore[$key] = $value;
        });

        $plugin = self::loadPlugin("count_vowels_kvstore.wasm", [$kvRead, $kvWrite]);

        $response = $plugin->call("count_vowels", "Hello World!");
        $this->assertEquals('{"count":3,"total":3,"vowels":"aeiouAEIOU"}', $response);
    }

    public static function loadPlugin(string $name, array $functions, ?callable $config = null)
    {
        $path = __DIR__ . '/../wasm/' . $name;
        $manifest = new Manifest(new PathWasmSource($path, 'main'));

        if ($config !== null) {
            $config($manifest);
        }

        return new Plugin($manifest, $functions, true);
    }
}

?>