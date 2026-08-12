<?php

namespace WpLibs\Kernel;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Default PSR-3 logger adapter.
 *
 * Writes log messages to the WordPress error log (error_log) so the library
 * works without an external logging package.
 */
class LoggerAdapter implements LoggerInterface
{
    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        if ( ! is_string( $message ) ) {
            $message = wp_json_encode( $message );
        }

        $line = sprintf( '[wp-libs][%s] %s', strtoupper( (string) $level ), $message );
        if ( ! empty( $context ) ) {
            $line .= ' ' . wp_json_encode( $context );
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log( $line );
    }
}
