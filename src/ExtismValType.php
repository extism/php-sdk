<?php

namespace Extism;

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
