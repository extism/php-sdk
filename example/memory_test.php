<?php
use Extism\PathWasmSource;
use Extism\UrlWasmSource;
use Extism\Manifest;
use Extism\Plugin;
use Extism\HostFunction;
use Extism\ExtismValType;
use Extism\CurrentPlugin;

require_once __DIR__ . "/../src/Plugin.php";
require_once __DIR__ . "/../src/HostFunction.php";

$wasm = new PathWasmSource(__DIR__ . "/../wasm/count_vowels_kvstore.wasm");
$manifest = new Manifest($wasm);

for ($i = 0; $i < 10_000; $i++){
    $kvstore = [];

    $kvRead = new HostFunction("kv_read", [ExtismValType::I64], [ExtismValType::I64], function (CurrentPlugin $p, string $key) use (&$kvstore) {
        return $kvstore[$key] ?? "\0\0\0\0";
    });

    $kvWrite = new HostFunction("kv_write", [ExtismValType::I64, ExtismValType::I64], [], function (string $key, string $value) use (&$kvstore) {
        $kvstore[$key] = $value;
    });

    $plugin = new Plugin($manifest, true, [$kvRead, $kvWrite]);
    $output = $plugin->call("count_vowels", "Hello World!");

    if ($i % 100 === 0) {
        echo "Iteration: $i\n";
    }
}

readline();