<?php

use Extism\Plugin;
use Extism\Manifest;
use Extism\Manifest\UrlWasmSource;

require "../vendor/autoload.php";

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
