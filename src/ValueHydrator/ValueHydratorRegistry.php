<?php

declare(strict_types=1);

namespace GeneratedHydrator\Bridge\Symfony\ValueHydrator;

use GeneratedHydrator\Bridge\Symfony\Error\ValueHydratorDoesNotExistError;

class ValueHydratorRegistry
{
    private array $typeCache = [];

    public function __construct(
        private array $instances = [],
    ) {}

    public function hasValueHydrator(string $toType): bool
    {
        try {
            $this->getValueHydrator($toType);
            return true;
        } catch (ValueHydratorDoesNotExistError) {
            return false;
        }
    }

    public function getValueHydrator(string $toType): ValueHydrator
    {
        $index = ($this->typeCache[$toType] ?? null);

        if (null !== $index) {
            return $this->instances[$index];
        }

        $found = null;
        foreach ($this->instances as $index => $instance) {
            if ($instance->supports($toType)) {
                $this->typeCache[$toType] = $index;
                $found = $instance;
                break;
            }
        }

        return $found ?? throw new ValueHydratorDoesNotExistError(\sprintf("No hydrator for type '%s' was found.", $toType));
    }
}
