<?php

namespace App\Kernel\Exceptions;

use Throwable;
use App\Kernel\Exceptions\ExceptionNormalizer;
use App\Kernel\Event\EventDispatcher;
use App\Kernel\Event\Messages\ExceptionEvent;

/**
 * Centralized exception handler
 * 
 * This class is responsible for handling all exceptions in a consistent way
 * and dispatching them as events for logging, monitoring, or notifications.
 */
class ExceptionHandler
{
    // Normalizer instance to standardize exception data (currently unused)
    protected static ExceptionNormalizer $normalizer;

    /**
     * Initialize the exception handler
     * Creates an instance of ExceptionNormalizer
     */
    public static function init(): void
    {
        self::$normalizer = new ExceptionNormalizer();
    }

    /**
     * Handle any throwable exception
     * 
     * @param Throwable $exception
     */
    public static function handle(Throwable $exception): void
    {
        /* Optional lazy initialization of the normalizer
        if (!isset(self::$normalizer)) {
            self::init();
        }
        */

        // Dispatch an event containing exception details
        // This allows other parts of the system (e.g., logging, notifications)
        // to react to the exception in a decoupled way
        EventDispatcher::dispatch(ExceptionEvent::from($exception));
    }

    /*
    // Optional method to log exceptions based on their log level
    function handleException(InterfaceExceptions $e) {
        switch ($e->getLogLevel()) {
            case 'critical':
                $logger->critical($e->getMessage());
                break;
            case 'warning':
                $logger->warning($e->getMessage());
                break;
            case 'error':
            default:
                $logger->error($e->getMessage());
                break;
        }
    }*/
}
