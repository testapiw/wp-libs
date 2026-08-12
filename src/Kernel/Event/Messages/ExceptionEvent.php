<?php

namespace WpLibs\Kernel\Event\Messages;

use Throwable;

/**
 * Event payload wrapping a Throwable.
 */
class ExceptionEvent
{
    protected Throwable $exception;

    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    public static function from(Throwable $exception): self
    {
        return new self( $exception );
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }
}
