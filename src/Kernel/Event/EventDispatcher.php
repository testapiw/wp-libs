<?php

namespace WpLibs\Kernel\Event;

/**
 * Minimal event dispatcher.
 *
 * Dispatches events to registered listeners. Kept intentionally simple so the
 * library works without an external event system.
 */
class EventDispatcher
{
    /** @var array<string, callable[]> */
    protected static array $listeners = [];

    public static function addListener(string $event, callable $listener): void
    {
        self::$listeners[ $event ][] = $listener;
    }

    public static function dispatch(object $event): void
    {
        $name = get_class( $event );
        if ( empty( self::$listeners[ $name ] ) ) {
            return;
        }

        foreach ( self::$listeners[ $name ] as $listener ) {
            call_user_func( $listener, $event );
        }
    }
}
