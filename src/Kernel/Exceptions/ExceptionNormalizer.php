<?php

namespace WpLibs\Kernel\Exceptions;

use Throwable;

/**
 * Normalizes a Throwable into a standard array shape.
 */
class ExceptionNormalizer
{
    public function normalize(Throwable $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $exception->getTraceAsString(),
        ];
    }
}
