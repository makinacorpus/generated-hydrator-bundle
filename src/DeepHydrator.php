<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

declare(strict_types=1);

namespace GeneratedHydrator\Bridge\Symfony;

use GeneratedHydrator\Bridge\Symfony\Error\CannotHydrateValueError;
use GeneratedHydrator\Bridge\Symfony\Error\ValueHydratorDoesNotExistError;
use GeneratedHydrator\Bridge\Symfony\HydrationPlan\HydratedProperty;
use GeneratedHydrator\Bridge\Symfony\HydrationPlan\HydrationPlan;
use GeneratedHydrator\Bridge\Symfony\HydrationPlan\HydrationPlanBuilder;
use GeneratedHydrator\Bridge\Symfony\HydrationPlan\ReflectionHydrationPlanBuilder;
use GeneratedHydrator\Bridge\Symfony\ValueHydrator\ValueHydratorRegistry;

/**
 * Use hydration plan to hydrated nested/deep objects graphs.
 */
final class DeepHydrator implements Hydrator
{
    private ClassBlacklist $classBlacklist;
    private Hydrator $decorated;
    private HydrationPlanBuilder $hydrationPlanBuilder;
    private ?ValueHydratorRegistry $valueHydratorRegistry = null;
    /** @var array<string, HydrationPlan> */
    private array $hydrationPlan = [];

    public function __construct(
        Hydrator $decorated,
        ?HydrationPlanBuilder $hydrationPlanBuilder = null,
        ?ClassBlacklist $classBlacklist = null,
        ?ValueHydratorRegistry $valueHydratorRegistry = null,
    ) {
        $this->decorated = $decorated;
        $this->hydrationPlanBuilder = $hydrationPlanBuilder ?? new ReflectionHydrationPlanBuilder();
        $this->classBlacklist = $classBlacklist ?? new ClassBlacklist();
        $this->valueHydratorRegistry = $valueHydratorRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(object $object, array $values, ?string $context = null): void
    {
        $this->decorated->hydrate($this->hydrateValues(\get_class($object), $values), $object,);
    }

    /**
     * {@inheritdoc}
     */
    public function createAndHydrate(string $className, array $values, ?string $context = null): object
    {
        return $this->decorated->createAndHydrate($className, $this->hydrateValues($className, $values));
    }

    /**
     * {@inheritdoc}
     */
    public function extract(object $object, ?string $context = null): array
    {
        return $this->extractValues(\get_class($object), $this->decorated->extract($object));
    }

    private function getHydrationPlan(string $className): HydrationPlan
    {
        return $this->hydrationPlan[$className] ?? ($this->hydrationPlan[$className] = $this->hydrationPlanBuilder->build($className));
    }

    private function hydrateValues(string $className, array $values): array
    {
        $hydrationPlan = $this->getHydrationPlan($className);

        if ($hydrationPlan->isEmpty()) {
            return $values;
        }

        // We do not validate property types, this is not our job, the business
        // layer asking for hydration should already have done that properly
        // and PHP since 7.4 will do proper type validation if properties are
        // typed. This is includes nullable as well.
        foreach ($hydrationPlan->getProperties() as $property) {
            \assert($property instanceof HydratedProperty);

            $converted = false;
            $isTypeCompatible = false;
            $propertyName = $property->name;

            if (null === ($value = $values[$propertyName] ?? null)) {
                continue;
            }

            $valueIsObject = \is_object($value);

            if ($this->classBlacklist->isBlacklisted($property->className)) {
                continue;
            }

            foreach ($property->nativeTypes as $phpType) {
                if (\get_debug_type($value) === $phpType || ($valueIsObject && $value instanceof $phpType)) {
                    // Property is already an object with the right class, let it
                    // pass gracefully the caller already has hydrated the object.
                    $isTypeCompatible = true;
                    break;
                }

                if ($this->valueHydratorRegistry && $this->valueHydratorRegistry->hasValueHydrator($phpType)) {
                    try {
                        $values[$propertyName] = $this->valueHydratorRegistry->getValueHydrator($phpType)->hydrate($phpType, $value);
                        $converted = true;
                        break;
                    } catch (ValueHydratorDoesNotExistError|CannotHydrateValueError) {}
                }
            }

            if ($converted || $isTypeCompatible) {
                continue;
            }

            if ($property->collection) {
                // @todo Deal with collections properly.
                continue;
            }

            // First start with complex types.
            foreach ($property->nativeTypes as $phpType) {
                if (!\class_exists($phpType)) {
                    continue;
                }
                if (!\is_array($value)) {
                    throw new CannotHydrateValueError(\sprintf("Property %s::$%s cannot hydrate an object from a non array value.", $property->className, $property->name));
                }
                $values[$propertyName] = $this->createAndHydrate($phpType, $value);
                break;
            }

            // Then, allow primitive types to be treated as well.

            /*
            throw new \InvalidArgumentException(\sprintf(
                "'%s::%s' must be an instanceof of %s, %s given",
                $className, $propertyName, $property->className,
                (\is_object($value) ? \get_class($value) : \gettype($value))
            ));
             */
        }

        return $values;
    }

    private function extractValues(string $className, array $values): array
    {
        $hydrationPlan = $this->getHydrationPlan($className);

        if ($hydrationPlan->isEmpty()) {
            return $values;
        }

        // Let extraction be loose, and do not fail when properties don't
        // match the rightful type: we trust full blown, PHP instances to
        // be valid, especially when using PHP 7.4 that would never allow
        // type mismatch.
        // @todo handle nested collections
        foreach ($hydrationPlan->getProperties() as $property) {
            \assert($property instanceof HydratedProperty);

            if (null === ($value = $values[$property->name] ?? null)) {
                continue;
            }

            foreach ($property->nativeTypes as $phpType) {
                if ($this->classBlacklist->isBlacklisted($phpType)) {
                    continue;
                }
                if ($value instanceof $phpType) {
                    $values[$property->name] = $this->extract($value);
                }
            }
        }

        return $values;
    }
}
