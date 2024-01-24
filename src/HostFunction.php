<?php
declare(strict_types=1);
namespace Extism;

require_once __DIR__ . "/LibExtism.php";
require_once __DIR__ . "/CurrentPlugin.php";

class ExtismValType
{
    public const I32 = 0;
    public const I64 = 1;
    public const PTR = I64;
    public const F32 = 2;
    public const F64 = 3;
    public const V128 = 4;
    public const FUNC_REF = 5;
    public const EXTERN_REF = 6;
}

class HostFunction
{
    private \LibExtism $lib;
    private $callback;

    public \FFI\CData $handle;

    /**
     * Constructor
     * 
     * @param string $name Name of the function
     * @param array $inputTypes Array of input types. @see ExtismValType
     * @param array $outputTypes Array of output types
     * @param callable $callback Callback to invoke when the function is called
     * 
     * @example location description
     */
    function __construct(string $name, array $inputTypes, array $outputTypes, callable $callback)
    {
        global $lib;

        if ($lib == null) {
            $lib = new \LibExtism();
        }

        $this->lib = $lib;

        $inputs = [];

        for ($i = 0; $i < count($inputTypes); $i++) {
            $inputs[$i] = $this->lib->ffi->cast("ExtismValType", $inputTypes[$i]);
        }

        $outputs = [];

        for ($i = 0; $i < count($outputTypes); $i++) {
            $outputs[$i] = $this->lib->ffi->cast("ExtismValType", $outputTypes[$i]);
        }

        $func = function ($handle, $inputs, $n_inputs, $outputs, $n_outputs, $data) use ($callback, $lib) {
            try {
                $reflection = new \ReflectionFunction($callback);
                $arguments = $reflection->getParameters();
                $params = [];

                $currentPlugin = new CurrentPlugin($lib, $handle);

                // TODO: do more validations on arguments vs inputs

                $offs = 0;
                if (count($arguments) > 0) {
                    $type = $arguments[0]->getType();
                    if (
                        ($type != null && $type->getName() == "Extism\CurrentPlugin") ||
                        count($arguments) == $n_inputs + 1
                    ) {
                        array_push($params, $currentPlugin);
                        $offs = 1;
                    }
                }

                for ($i = 0; $i < $n_inputs; $i++) {
                    $input = $inputs[$i];

                    switch ($input->t) {
                        case ExtismValType::I32:
                            array_push($params, $input->v->i32);
                            break;
                        case ExtismValType::I64:
                            $type = $arguments[$i + $offs]->getType();

                            if ($type != null && $type->getName() == "string") {
                                $ptr = $input->v->i64;
                                $str = $currentPlugin->read_block($ptr);
                                array_push($params, $str);
                            } else {
                                array_push($params, $input->v->i64);
                            }

                            break;
                        case ExtismValType::F32:
                            array_push($params, $input->v->f32);
                            break;
                        case ExtismValType::F64:
                            array_push($params, $input->v->f64);
                            break;
                        default:
                            throw new \Exception("Unsupported type for parametr #$i: " . $input->t);
                    }
                }

                $r = $callback(...$params);

                if ($r == NULL) {
                    $r = 0;
                } else if (gettype($r) == "string") {
                    $r = $currentPlugin->write_block($r);
                }

                if ($n_outputs == 1) {
                    $output = $outputs[0];

                    switch ($output->t) {
                        case ExtismValType::I32:
                            $output->v->i32 = $r;
                            break;
                        case ExtismValType::I64:
                            $output->v->i64 = $r;
                            break;
                        case ExtismValType::F32:
                            $output->v->f32 = $r;
                            break;
                        case ExtismValType::F64:
                            $output->v->f64 = $r;
                            break;
                        default:
                            throw new \Exception("Unsupported type for output: " . $output->t);
                    }
                }
            } catch (\Throwable $e) { // For PHP 7
                echo "Exception: " . $e->getMessage() . PHP_EOL;
            } catch (\Exception $e) { // For PHP 5
                echo "Exception: " . $e->getMessage() . PHP_EOL;
            }
        };

        $this->callback = $func;

        $this->handle = $this->lib->extism_function_new($name, $inputs, $outputs, $func, null, null);
        $this->set_namespace("extism:host/user");
    }

    function set_namespace(string $namespace)
    {
        $this->lib->extism_function_set_namespace($this->handle, $namespace);
    }
}
