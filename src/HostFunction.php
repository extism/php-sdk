<?php
declare(strict_types=1);
namespace Extism;

require_once __DIR__ . "/LibExtism.php";
require_once __DIR__ . "/CurrentPlugin.php";

class HostFunction
{
    private \LibExtism $lib;
    private $callback;

    public \FFI\CData $handle;

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
            $reflection = new \ReflectionFunction($callback);
            $arguments = $reflection->getParameters();
            $params = [];

            $currentPlugin = new CurrentPlugin($lib, $handle);

            // TODO: do more validations on arguments vs inputs

            $offs = 0;
            if (count($arguments) > 0) {
                $type = $arguments[0]->getType();
                if (($type != null && $type->getName() == "Extism\CurrentPlugin") ||
                    count($arguments) == $n_inputs + 1) {
                    array_push($params, $currentPlugin);
                    $offs = 1;
                }
            }

            for ($i = 0; $i < $n_inputs; $i++) {
                $input = $inputs[$i];

                switch ($input->t) {
                    case \ExtismValType::I32:
                        array_push($params, $input->v->i32);
                        break;
                    case \ExtismValType::I64:
                        $type = $arguments[$i + $offs]->getType();

                        if ($type != null && $type->getName() == "string") {
                            $ptr = $input->v->i64;
                            $str = $currentPlugin->read_memory($ptr);
                            array_push($params, $str);
                        } else {
                            array_push($params, $input->v->i64);
                        }
                        break;
                    case \ExtismValType::F32:
                        array_push($params, $input->v->f32);
                        break;
                    case \ExtismValType::F64:
                        array_push($params, $input->v->f64);
                        break;
                    default:
                        throw new \Exception("Unsupported type for parametr #$i: " . $input->t);
                }
            }

            $r = $callback(...$params);

            if (gettype($r) == "string") {
                $r = $currentPlugin->write_memory($r);
            }

            if ($n_outputs == 1) {
                $output = $outputs[0];

                switch ($output->t) {
                    case \ExtismValType::I32:
                        $output->v->i32 = $r;
                        break;
                    case \ExtismValType::I64:
                        $output->v->i64 = $r;
                        break;
                    case \ExtismValType::F32:
                        $output->v->f32 = $r;
                        break;
                    case \ExtismValType::F64:
                        $output->v->f64 = $r;
                        break;
                    default:
                        throw new \Exception("Unsupported type for output: " . $output->t);
                }
            }
        };

        $this->callback = $func;

        $this->handle = $this->lib->extism_function_new($name, $inputs, $outputs, $func, null, null);
        $this->lib->extism_function_set_namespace($this->handle, "extism:host/user");
    }
}
