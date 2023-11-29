# Extism PHP Host SDK

This repo houses the PHP SDK for integrating with the [Extism](https://extism.org/) runtime. Install this library into your host PHP applications to run Extism plugins.

## Installation

### Install the Extism Runtime Dependency

For this library, you first need to install the Extism Runtime. You can [download the shared object directly from a release](https://github.com/extism/extism/releases) or use the [Extism CLI](https://github.com/extism/cli) to install it:

```bash
sudo extism lib install latest

#=> Fetching https://github.com/extism/extism/releases/download/v0.5.2/libextism-aarch64-apple-darwin-v0.5.2.tar.gz
#=> Copying libextism.dylib to /usr/local/lib/libextism.dylib
#=> Copying extism.h to /usr/local/include/extism.h
```

> **Note**: This library has breaking changes and targets 1.0 of the runtime. For the time being, install the runtime from our nightly development builds on git: `sudo extism lib install --version git`.

### Install the Package

Install via [Packagist](https://packagist.org/):
```sh
composer require extism/extism
```

*Note*: For the time being you may need to add a minimum-stability of "dev" to your composer.json
```json
{
   "minimum-stability": "dev",
}
```

## Getting Started

This guide should walk you through some of the concepts in Extism and this PHP library.

First you should add a using statement for Extism:

```php
use Extism\Plugin;
use Extism\Manifest;
use Extism\UrlWasmSource;
```

## Creating A Plug-in

The primary concept in Extism is the [plug-in](https://extism.org/docs/concepts/plug-in). You can think of a plug-in as a code module stored in a `.wasm` file.

Since you may not have an Extism plug-in on hand to test, let's load a demo plug-in from the web:

```php
$wasm = new UrlWasmSource("https://github.com/extism/plugins/releases/latest/download/count_vowels.wasm");
$manifest = new Manifest($wasm);

$plugin = new Plugin($manifest, true);
```

> **Note**: The schema for this manifest can be found here: https://extism.org/docs/concepts/manifest/

### Calling A Plug-in's Exports

This plug-in was written in Rust and it does one thing, it counts vowels in a string. As such, it exposes one "export" function: `count_vowels`. We can call exports using `Plugin.call`:

```php
$output = $plugin->call("count_vowels", "Hello, World!");

// => {"count": 3, "total": 3, "vowels": "aeiouAEIOU"}
```

All exports have a simple interface of optional bytes in, and optional bytes out. This plug-in happens to take a string and return a JSON encoded string with a report of results.

### Plug-in State

Plug-ins may be stateful or stateless. Plug-ins can maintain state b/w calls by the use of variables. Our count vowels plug-in remembers the total number of vowels it's ever counted in the "total" key in the result. You can see this by making subsequent calls to the export:

```php
$output = $plugin->call("count_vowels", "Hello, World!");
// => {"count": 3, "total": 6, "vowels": "aeiouAEIOU"}

$output = $plugin->call("count_vowels", "Hello, World!");
// => {"count": 3, "total": 9, "vowels": "aeiouAEIOU"}
```

These variables will persist until this plug-in is freed or you initialize a new one.

### Configuration

Plug-ins may optionally take a configuration object. This is a static way to configure the plug-in. Our count-vowels plugin takes an optional configuration to change out which characters are considered vowels. Example:

```php
$wasm = new UrlWasmSource("https://github.com/extism/plugins/releases/latest/download/count_vowels.wasm");

$manifest = new Manifest($wasm);

$plugin = new Plugin($manifest, true);
$output = $plugin->call("count_vowels", "Yellow, World!");
// => {"count": 3, "total": 3, "vowels": "aeiouAEIOU"}

$manifest = new Manifest($wasm);
$manifest->config->vowels = "aeiouyAEIOUY";

$plugin = new Plugin($manifest, true);
$output = $plugin->call("count_vowels", "Yellow, World!");
// => {"count": 4, "total": 4, "vowels": "aeiouAEIOUY"}
```

