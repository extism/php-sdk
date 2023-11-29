<?php

require_once __DIR__ . "/../src/Plugin.php";

\Extism\Plugin::setLogFile("log.txt", "trace");

$wasm = file_get_contents("../wasm/count_vowels.wasm");
$plugin = \Extism\Plugin::fromBytes($wasm);

if ($plugin->functionExists("count_vowels")) {
    echo "count_vowels exists!" . PHP_EOL;
} else {
    echo "count_vowels doesn't exist!" . PHP_EOL;
}

$result = $plugin->call("count_vowels", "");
echo $result . PHP_EOL;
echo "DONE!";