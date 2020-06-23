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

use GeneratedHydrator\Bridge\Symfony\HydrationPlan\HydratedProperty;
use GeneratedHydrator\Bridge\Symfony\HydrationPlan\HydrationPlan;
use GeneratedHydrator\Bridge\Symfony\HydrationPlan\HydrationPlanBuilder;
use GeneratedHydrator\Bridge\Symfony\HydrationPlan\ReflectionHydrationPlanBuilder;
use GeneratedHydrator\Bridge\Symfony\Utils\ClassBlacklist;

/**
 * Use hydration plan to hydrated nested/deep objects graphs.
 */
final class DeepHydrator implements Hydrator
{
    /** @var ClassBlacklist */
    private $classBlacklist;

    /** @var Hydrator */
    private $hydrator;

    /** @var HydrationPlanBuilder */
    private $hydrationPlanBuilder;

    /** @var array<string, HydrationPlan> */
    private $hydrationPlan = [];

    /**
     * Default constructor
     */
    public function __construct(Hydrator $hydrator, ?HydrationPlanBuilder $hydrationPlanBuilder = null, ?ClassBlacklist $classBlacklist = null)
    {
        $this->hydrator = $hydrator;
        $this->hydrationPlanBuilder = $hydrationPlanBuilder ?? new ReflectionHydrationPlanBuilder();
        $this->classBlacklist = $classBlacklist ?? new ClassBlacklist();
    }

    /**
     * Get hydration plan for class
     */
    private function getHydrationPlan(string $className): HydrationPlan
    {
        return $this->hydrationPlan[$className] ?? (
            $this->hydrationPlan[$className] = $this->hydrationPlanBuilder->build($className)
        );
    }

    /**
     * Process nested object hydration
     */
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

            $propertyName = $property->name;

            if (null === ($value = $values[$propertyName] ?? null)) {
                continue;
            }

            if ($this->classBlacklist->isBlacklisted($property->className)) {
                continue;
            }

            if ($value instanceof $property->className) {
                // Property is already an object with the right class, let it
                // pass gracefully the caller already has hydrated the object.
                continue;
            }

            if ($property->collection) {
                // @todo
                // Deal with collections properly.
                continue;
            }

            if (\is_array($value)) {
                $values[$propertyName] = $this->createAndHydrate($property->className, $value);
            }

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

    /**
     * {@inheritdoc}
     */
    public function hydrate(object $object, array $values): void
    {
        $className = \get_class($object);

        $this->hydrator->hydrate(
            $this->hydrateValues($className, $values),
            $object
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createAndHydrate(string $className, array $values): object
    {
        return $this->hydrator->createAndHydrate(
            $className,
            $this->hydrateValues($className, $values)
        );
    }

    /**
     * Process nested object hydration
     */
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

            $propertyName = $property->name;

            if (null === ($value = $values[$propertyName] ?? null)) {
                continue;
            }

            if ($this->classBlacklist->isBlacklisted($property->className)) {
                continue;
            }

            // Skip properties that don't have the rightful type, this
            // could be because the hydration plan is wrong.
            if ($value instanceof $property->className) {
                $values[$propertyName] = $this->extract($value);
            }
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function extract(object $object): array
    {
        $className = \get_class($object);

        return $this->extractValues(
            $className,
            $this->hydrator->extract($object)
        );
    }
}
