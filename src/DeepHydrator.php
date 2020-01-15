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

/**
 * Use hydration plan to hydrated nested/deep objects graphs.
 */
final class DeepHydrator implements Hydrator
{
    /** @var Hydrator */
    private $hydrator;

    /** @var HydrationPlanBuilder */
    private $hydrationPlanBuilder;

    /** @var array<string, HydrationPlan> */
    private $hydrationPlan = [];

    /**
     * Default constructor
     */
    public function __construct(Hydrator $hydrator, ?HydrationPlanBuilder $hydrationPlanBuilder = null)
    {
        $this->hydrator = $hydrator;
        $this->hydrationPlanBuilder = $hydrationPlanBuilder ?? new ReflectionHydrationPlanBuilder();
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
    private function processValues(string $className, array $values): array
    {
        $hydrationPlan = $this->getHydrationPlan($className);

        // @todo handle this in extract() too
        if ($hydrationPlan->isEmpty()) {
            return $values;
        }

        foreach ($hydrationPlan->getProperties() as $property) {
            \assert($property instanceof HydratedProperty);

            // @todo Should we add non nullable properties validation here?
            // @todo Should we really handle collections as well?
            $propertyName = $property->name;

            if (null === ($value = $values[$propertyName] ?? null)) {
                continue;
            }
            if ($value instanceof $property->className) {
                // Property is already an object with the right class, let it
                // pass gracefully the caller already has hydrated the object.
                continue;
            }

            if (!\is_array($value)) {
                throw new \InvalidArgumentException(\sprintf(
                    "'%s::%s' must be an instanceof of %s, %s given",
                    $className, $propertyName, $property->className,
                    (\is_object($value) ? \get_class($value) : \gettype($value))
                ));
            }

            $values[$propertyName] = $this->createAndHydrate($property->className, $value);
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
            $this->processValues($className, $values),
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
            $this->processValues($className, $values)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function extract(object $object): array
    {
        return $this->hydrator->extract($object);
    }
}
