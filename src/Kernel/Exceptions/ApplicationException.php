<?php

namespace App\Kernel\Exceptions;

use Exception;
use App\Kernel\Exceptions\InterfaceExceptions;

/**
 * Application-level exception class
 * Used for predictable, domain-specific errors in the application
 */
class ApplicationException extends Exception implements InterfaceExceptions
{
    // Custom error codes for specific application errors
    const MISSING_ID = 1001;        // Error code when an expected ID is missing
    const NO_VALID_FIELDS = 1002;   // Error code when no valid fields are provided

    // Default log level for this exception
    const LOG_LEVEL = 'error';
    
    /**
     * Get the log level for this exception
     * 
     * @return string Returns a PSR-3 compatible log level
     */
    public function getLogLevel(): string {
        return static::LOG_LEVEL;
    }
}