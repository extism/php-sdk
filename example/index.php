<?php
use Extism\UrlWasmSource;
use Extism\Manifest;
use Extism\Plugin;

require_once __DIR__ . "/../src/Plugin.php";

$wasm = new UrlWasmSource("https://github.com/extism/plugins/releases/latest/download/count_vowels.wasm");
$manifest = new Manifest($wasm);

$plugin = new Plugin($manifest, true);
$output = $plugin->call("count_vowels", "Yellow, World!");
var_dump($output);

$manifest = new Manifest($wasm);
$manifest->config->vowels = "aeiouyAEIOUY";

$plugin = new Plugin($manifest, true);
$output = $plugin->call("count_vowels", "Yellow, World!");
var_dump($output);


// \Extism\Plugin::setLogFile("log.txt", "trace");

// $wasm = file_get_contents("../wasm/count_vowels.wasm");
// $plugin = \Extism\Plugin::fromBytes($wasm);

// if ($plugin->functionExists("count_vowels")) {
//     echo "count_vowels exists!" . PHP_EOL;
// } else {
//     echo "count_vowels doesn't exist!" . PHP_EOL;
// }

// $result = $plugin->call("count_vowels", "");
// echo $result . PHP_EOL;
// echo "DONE!";