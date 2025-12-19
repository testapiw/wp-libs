<?php

namespace App\Utils;


// LIBS_DIR or theme root directory
class Templates
{
    private static $base = LIBS_DIR . 'templates/';

    public static function render(string $path, array $data = [],?string $base = null) 
    {
        $base = $base ?? self::$base;
        $file = $base . ltrim($path, '/'). '.php';
        if (file_exists($file)) {
            ob_start();
            require $file; 
            return ob_get_clean();  
        }
        return ''; 
    }
}