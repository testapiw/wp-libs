<?php

namespace WpLibs\Kernel\Contracts;

interface ImageInterface
{
    public function save(string $imageData, string $filePath): array;
}
