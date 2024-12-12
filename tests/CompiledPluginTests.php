<?php

declare(strict_types=1);

namespace Extism\Tests;

use Extism\CompiledPlugin;
use Extism\CurrentPlugin;
use Extism\HostFunction;
use PHPUnit\Framework\TestCase;
use Extism\Plugin;
use Extism\Manifest;
use Extism\Manifest\PathWasmSource;
use Extism\ExtismValType;

final class CompiledPluginTests extends TestCase
{
    public function testCompiledCountVowels(): void
    {
        $compiledPlugin  = self::compilePlugin("count_vowels.wasm", []);
        $plugin = $compiledPlugin->instantiate();

        $response = $plugin->call("count_vowels", "Hello World!");
        $actual = json_decode($response);

        $this->assertEquals(3, $actual->count);
    }

    public function testCompiledHostFunctions(): void
    {
        $kvstore = [];

        $kvRead = new HostFunction("kv_read", [ExtismValType::I64], [ExtismValType::I64], function (CurrentPlugin $p, string $key) use (&$kvstore) {
            return $kvstore[$key] ?? "\0\0\0\0";
        });

        $kvWrite = new HostFunction("kv_write", [ExtismValType::I64, ExtismValType::I64], [], function (string $key, string $value) use (&$kvstore) {
            $kvstore[$key] = $value;
        });

        $compiledPlugin = self::compilePlugin("count_vowels_kvstore.wasm", [$kvRead, $kvWrite]);

        $plugin = $compiledPlugin->instantiate();

        $response = $plugin->call("count_vowels", "Hello World!");
        $this->assertEquals('{"count":3,"total":3,"vowels":"aeiouAEIOU"}', $response);

        $response = $plugin->call("count_vowels", "Hello World!");
        $this->assertEquals('{"count":3,"total":6,"vowels":"aeiouAEIOU"}', $response);
    }

    public static function compilePlugin(string $name, array $functions, ?callable $config = null)
    {
        $path = __DIR__ . '/../wasm/' . $name;
        $manifest = new Manifest(new PathWasmSource($path, 'main'));

        if ($config !== null) {
            $config($manifest);
        }

        return new CompiledPlugin($manifest, $functions, true);
    }
}
