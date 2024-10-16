<?php

class PluginLoadException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}