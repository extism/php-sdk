# Extism PHP Host SDK

# Using the PHP Host SDK

> *Note*: Please be sure you've [installed Extism](/docs/install) before continuing with this guide.

### 1. Install the PHP library

Install via [Packagist](https://packagist.org/):
```sh
composer require extism/extism
```

> *Note*: For the time being you may need to add a minimum-stability of "dev" to your composer.json
> ```json
> {
>    "minimum-stability": "dev",
> }
> ```

### 2. Import the library and use the APIs

> *Note*: `code.wasm` in this example is our example plugin that counts vowels. If you want to run this, download it first and set the path:
> ```
> curl https://raw.githubusercontent.com/extism/extism/main/wasm/code.wasm > code.wasm
> ```

```php title=index.php
<?php

require_once __DIR__ . '/vendor/autoload.php';

// See the manifest docs for more options https://extism.org/docs/concepts/manifest
$wasm = (object) [ 'wasm' => [](object) [ 'path'] = './code.wasm']];

// (or, simpler but less efficiently: $wasm = file_get_contents("./code.wasm");

// NOTE: if you encounter an error such as: 
// "Unable to load plugin: unknown import: wasi_snapshot_preview1::fd_write has not been defined"
// pass `true` after $wasm in the following function to provide WASI imports to your plugin.
$plugin = new \Extism\Plugin($wasm);

$output = $plugin->call("count_vowels", "this is an example");
$json = json_decode(pack('C*', ...$output));
echo "Vowels counted = " . $json->{'count'} . PHP_EOL;
```

> *Note*: On some MacOS devices (particularly Apple Silicon), you may hit an error regarding the `Security.framework`. We're working on a solution here, but in the meantime, if this happens to you please file an issue or comment here: [https://github.com/extism/extism/issues/96](https://github.com/extism/extism/issues/96).

### Need help?

If you've encountered a bug or think something is missing, please open an issue on the [Extism GitHub](https://github.com/extism/extism) repository.

There is an active community on [Discord](https://discord.gg/cx3usBCWnc) where the project maintainers and users can help you. Come hang out!



