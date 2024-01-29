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
     * @example ../tests/PluginTest.php 82 84 Simple Example
     * @example ../tests/PluginTest.php 100 104 Manually read memory using CurrentPlugin
     */
    function __construct(string $name, array $inputTypes, array $outputTypes, callable $callback)
    {
        $reflection = new \ReflectionFunction($callback);
        $arguments = $reflection->getParameters();
        $offset = HostFunction::validate_arguments($arguments, $inputTypes);

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

        $func = function ($handle, $inputs, $n_inputs, $outputs, $n_outputs, $data) use ($callback, $lib, $arguments, $offset) {
            try {
                $currentPlugin = new CurrentPlugin($lib, $handle);
                $params = HostFunction::get_parameters($currentPlugin, $inputs, $n_inputs, $arguments, $offset);

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
            
                // Throwing an exception in FFI callback is not supported and
                // causes a fatal error without a stack trace.
                // So we catch it and print the exception manually
            } catch (\Throwable $e) { // PHP 7+
                HostFunction::print_exception($e);
            } catch (\Exception $e) { // PHP 5+
                HostFunction::print_exception($e);
            }
        };

        $this->callback = $func;

        $this->handle = $this->lib->extism_function_new($name, $inputs, $outputs, $func, null, null);
        $this->set_namespace("extism:host/user");
    }

    function __destruct()
    {
        $this->lib->extism_function_free($this->handle);
    }

    function set_namespace(string $namespace)
    {
        $this->lib->extism_function_set_namespace($this->handle, $namespace);
    }

    private static function print_exception(\Throwable $e)
    {
        echo "Exception thrown in host function: " . $e->getMessage() . PHP_EOL;
        echo $e->getTraceAsString() . PHP_EOL;
        throw $e;
    }

    private static function get_type_name(\ReflectionParameter $param)
    {
        $type = $param->getType();

        if ($type == null) {
            return null;
        }

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    private static function get_parameters(
        CurrentPlugin $currentPlugin, 
        \FFI\CData $inputs,
        int $n_inputs,
        array $arguments, 
        int $offset) : array
    {
        $params = [];

        if ($offset == 1) {
            array_push($params, $currentPlugin);
        }

        for ($i = 0; $i < $n_inputs; $i++) {
            $input = $inputs[$i];

            switch ($input->t) {
                case ExtismValType::I32:
                    array_push($params, $input->v->i32);
                    break;
                case ExtismValType::I64:
                    $type = HostFunction::get_type_name($arguments[$i + $offset]);

                    if ($type != null && $type == "string") {
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

        return $params;
    }

    private static function validate_arguments(array $arguments, array $inputTypes)
    {
        $offset = 0;
        $n_arguments = count($arguments);

        if ($n_arguments > 0 && HostFunction::get_type_name($arguments[0]) == "Extism\CurrentPlugin") {
            $offset = 1;
        }

        if ($n_arguments - $offset != count($inputTypes)) {
            throw new \Exception("Number of arguments does not match number of input types");
        }

        for ($i = $offset; $i < $n_arguments; $i++) {
            $argType = HostFunction::get_type_name($arguments[$i]);

            if ($argType == null) {
                continue;
            } else if ($argType == "string") {
                // string is represented as a pointer to a block of memory
                $argType = "int";
            }

            $inputType = $inputTypes[$i - $offset];

            switch ($inputType) {
                case ExtismValType::I32:
                case ExtismValType::I64:
                    if ($argType != "int") {
                        throw new \Exception("Argument #$i is not an int");
                    }
                    break;
                case ExtismValType::F32:
                case ExtismValType::F64:
                    if ($argType != "float") {
                        throw new \Exception("Argument #$i is not a float");
                    }
                    break;

                default:
                    throw new \Exception("Unsupported type for argument #$i: " . $inputType);
            }
        }

        return $offset;
    }
}
