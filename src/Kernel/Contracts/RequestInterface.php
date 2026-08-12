<?php

namespace WpLibs\Kernel\Contracts;

interface RequestInterface
{
    public function getFields(): array;
    public function getFilters(): array;
    public function setFilters(array $filters): static;
}
