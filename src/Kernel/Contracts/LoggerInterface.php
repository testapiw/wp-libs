<?php

namespace WpLibs\Kernel\Contracts;

interface LoggerInterface
{
   // public function info(string $msg);
   // public function warning(string $msg);
   // public function error(string $msg);

    public function info($message, array $context = []);
    public function warning($message, array $context = []);
    public function error($message, array $context = []);
    public function log($level, $message, array $context = []);
}
