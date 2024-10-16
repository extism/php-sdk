<?php

namespace Extism;

class FunctionCallException extends \Exception
{
    public string $error;
    public string $functionName;

    public function __construct(string $message, string $error, string $functionName)
    {
        parent::__construct($message);

        $this->error = $error;
        $this->functionName = $functionName;
    }
}