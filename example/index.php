<?php
use Extism\CurrentPlugin;
use Extism\HostFunction;
use Extism\PathWasmSource;
use Extism\UrlWasmSource;
use Extism\Manifest;
use Extism\Plugin;

require_once __DIR__ . "/../src/Plugin.php";
require_once __DIR__ . "/../src/HostFunction.php";

//$wasm = new UrlWasmSource("https://github.com/extism/plugins/releases/latest/download/count_vowels.wasm");
$wasm = new PathWasmSource("D:/dylibso/go-pdk/example/reactor/c.wasm");
$manifest = new Manifest($wasm);

$function = new HostFunction("echo", [ExtismValType::I64], [ExtismValType::I64], function(CurrentPlugin $currentPlugin, string $message) {
    echo "Plugin says: " . $message;
    return "Hello from the other side!";
});

$functions = [$function];

$plugin = new Plugin($manifest, $functions, true);
$output = $plugin->call("say_hello", "");


// $manifest = new Manifest($wasm);
// $manifest->config->vowels = "aeiouyAEIOUY";

// $plugin = new Plugin($manifest, true);
// $output = $plugin->call("count_vowels", "Yellow, World!");
// var_dump($output);