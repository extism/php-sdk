<?
use Extism\Plugin;
use Extism\Manifest;
use Extism\PathWasmSource;

class Helpers {
    public static function loadPlugin(string $name, ?callable $config = null)
    {
        $path = __DIR__ . '/../wasm/' . $name;
        $manifest = new Manifest(new PathWasmSource($path, 'main'));
    
        if ($config !== null) {
            $config($manifest);
        }
    
        return new Plugin($manifest, true);
    }
    
}

?>