<?php

namespace App\Kernel\Exceptions;

/**
 * Interface for custom application exceptions
 * 
 * Ensures that any exception implementing this interface provides a log level.
 */
interface InterfaceExceptions {
    /**
     * Get the log level for this exception
     * 
     * @return string A PSR-3 compatible log level (e.g., 'error', 'warning', 'critical')
     */
    public function getLogLevel(): string;
}