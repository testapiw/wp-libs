<?php

namespace WpLibs\Kernel\Queries;

use WpLibs\Kernel\Contracts\DefinitionInterface;

class QueryObject
{
    private array $filters = [];
    private ?DefinitionInterface $definition = null;
    private ?array $order = null;
    private ?array $pagination = null;
    private array $updateFields = [];

    public function __construct(
        array $filters = [],
        ?DefinitionInterface $definition = null
    ) {
        $this->filters = $filters;
        $this->definition = $definition;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): static
    {
        $this->filters = $filters;
        return $this;
    }

    public function getDefinition(): ?DefinitionInterface
    {
        return $this->definition;
    }

    public function setDefinition(DefinitionInterface $definition): static
    {
        $this->definition = $definition;
        return $this;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function setOrder(array $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getPagination(): ?array
    {
        return $this->pagination;
    }

    public function setPagination(array $pagination): static
    {
        $this->pagination = $pagination;
        return $this;
    }

    public function setUpdateFields(array $updateFields): static
    {
        $this->updateFields = $updateFields;
        return $this;
    }

    public function getUpdateFields(): array
    {
        return $this->updateFields;
    }    

    // ------------

    public function toArray(): array
    {
        return $this->getData($this->filters);
    }

    private function getData(array $filter): array
    {
        $result = [];

        if (!isset($filter['conditions']) || !is_array($filter['conditions'])) {
            return $result;
        }

        foreach ($filter['conditions'] as $condition) {
            if (isset($condition['conditions'])) {
                $result = array_merge($result, $this->getData($condition));
            }
            elseif (isset($condition['field']) && array_key_exists('value', $condition)) {
                $result[$condition['field']] = $condition['value'];
            }
        }

        return $result;
    }
}
