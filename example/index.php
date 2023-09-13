<?php

require_once __DIR__ . '/vendor/autoload.php';

$wasm = file_get_contents("../wasm/count-vowels.wasm");
$plugin = new \Extism\Plugin($wasm);

$output = $plugin->call("count_vowels", "this is an example");
$json = json_decode(pack('C*', ...$output));
echo "Vowels counted = " . $json->{'count'} . PHP_EOL;
