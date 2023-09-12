<?php

declare(strict_types=1);

namespace GeneratedHydrator\Bridge\Symfony\ValueHydrator;

/**
 * Allow to plugin site-wide logic for hydrating types.
 */
interface ValueHydrator
{
    public function supports(string $phpType): bool;

    public function hydrate(string $phpType, mixed $value): mixed;
}
