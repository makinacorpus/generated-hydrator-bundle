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

namespace GeneratedHydrator\Bridge\Symfony\HydrationPlan;

use GeneratedHydrator\Bridge\Symfony\Error\NotImplementedError;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;

/**
 * Caches the hydration plan
 */
final class ReflectionHydrationPlanBuilder implements HydrationPlanBuilder
{
    private ?PropertyTypeExtractorInterface $typeInfoExtractor = null;
    private bool $typeInfoExtractorLoaded = false;

    /**
     * Build hydration plan.
     *
     * @param string[] $propertyBlackList
     *   Properties to ignore.
     */
    public function build(string $className, array $propertyBlackList = []): HydrationPlan
    {
        $ref = new \ReflectionClass($className);

        $properties = [];
        foreach ($this->findAllProperties($ref) as $property) {
            \assert($property instanceof \ReflectionProperty);
            if ($definition = $this->findPropertyDefinition($className, $property->getName(), $property)) {
                $properties[] = $definition;
            }
        }

        return new HydrationPlan($className, $properties);
    }

    /**
     * Recursion to find all parent and traits included properties.
     *
     * @return \ReflectionProperty[]
     */
    private function findAllProperties(?\ReflectionClass $class): array
    {
        if (null === $class) {
            return [];
        }
        // Recursive algorithm that lookup into the whole class hierarchy.
        return \array_values(\array_merge(
            $this->findAllProperties($class->getParentClass() ?: null),
            \array_values(\array_filter(
                $class->getProperties(),
                function (\ReflectionProperty $property) : bool {
                    return !$property->isStatic();
                }
            ))
        ));
    }

    /**
     * Parse property definition.
     *
     * @return HydratedProperty[]
     *   Multiple occurence of the same property can exist, if more than
     *   one allowed type was found.
     */
    private function findPropertyDefinition(string $className, string $propertyName, \ReflectionProperty $property):  ?HydratedProperty
    {
        return
            $this->findPropertyWithReflection($className, $propertyName, $property) ??
            $this->findPropertyWithPropertyInfo($className, $propertyName, $property)
        ;
    }

    /**
     * Attempt to create a type info extractor if Symfony component is present.
     *
     * This can return null if the dependency was not installed, hence the
     * self::$typeInfoExtractorLoaded boolean to avoid redundant class and
     * interface existence checks.
     *
     * @codeCoverageIgnore
     */
    public static function createDefaultTypeInfoExtractor(bool $withCache = true): ?PropertyTypeExtractorInterface
    {
        if (\interface_exists(PropertyTypeExtractorInterface::class)) {
            $phpDocExtractor = new PhpDocExtractor();

            return new PropertyInfoExtractor(
                [],
                [$phpDocExtractor],
                [$phpDocExtractor],
                [],
                []
            );
        }
        return null;
    }

    /**
     * Collection types.
     */
    public static function isCollectionType(string $type): bool
    {
        return
            'array' === $type || 'iterable' === $type ||
            // From potential psalm or IDE known typings.
            'list' === $type ||
            \is_subclass_of($type, \Traversable::class)
        ;
    }

    /**
     * Excluded types
     */
    public static function isTypeBlacklisted(string $type): bool
    {
        return self::isCollectionType($type) || 'null' === $type || 'callable' === $type;
    }

    /**
     * Set type info extractor
     */
    public function setTypeInfoExtractor(PropertyTypeExtractorInterface $typeInfoExtractor): void
    {
        $this->typeInfoExtractorLoaded = true;
        $this->typeInfoExtractor = $typeInfoExtractor;
    }

    /**
     * Get type info extractor
     */
    private function getTypeInfoExtractor(): ?PropertyTypeExtractorInterface
    {
        if (!$this->typeInfoExtractorLoaded && !$this->typeInfoExtractor) {
            $this->typeInfoExtractorLoaded = true;
            $this->typeInfoExtractor = self::createDefaultTypeInfoExtractor();
        }
        return $this->typeInfoExtractor;
    }

    /**
     * Internal recursion for findPropertyWithReflection().
     */
    private function findPropertyWithReflectionType(string $className, string $propertyName, \ReflectionType $refType, array $types): array
    {
        if ($refType instanceof \ReflectionIntersectionType) {
            throw new NotImplementedError(\sprintf("%s::$%s: intersection types are not supported.", $className, $propertyName));
        }

        if ($refType instanceof \ReflectionUnionType) {
            foreach ($refType->getTypes() as $subType) {
                return $this->findPropertyWithReflectionType($className, $propertyName, $subType, $types);
            }
        }

        if ($refType instanceof \ReflectionNamedType) {
            $types[] = $refType->getName();

            return $types;
        }

        throw new NotImplementedError(\sprintf("%s::$%s: %s type are not supported.", $className, $propertyName, \get_class($refType)));
    }

    /**
     * Find property type using PHP native property type definition.
     */
    private function findPropertyWithReflection(string $className, string $propertyName, \ReflectionProperty $refProp): ?HydratedProperty
    {
        if (!$refType = $refProp->getType()) {
            return null;
        }

        if ($refType instanceof \ReflectionNamedType && self::isCollectionType($refType->getName())) {
            return new HydratedProperty(
                allowsNull: $refType->allowsNull(),
                className: $className,
                collection: false,
                complete: false, // Let other extractor find somethiing.
                name: $propertyName,
                nativeTypes: [],
            );
        }

        return new HydratedProperty(
            allowsNull: $refType->allowsNull(),
            className: $className,
            collection: false,
            name: $propertyName,
            nativeTypes: $this->findPropertyWithReflectionType($className, $propertyName, $refType, []),
        );
    }

    /**
     * Find property type using symfony/property-info.
     */
    private function findPropertyWithPropertyInfo(string $className, string $propertyName, \ReflectionProperty $property):  ?HydratedProperty
    {
        if (!$typeInfoExtractor = $this->getTypeInfoExtractor()) {
            return null;
        }
        if (!$propInfoTypeList = $typeInfoExtractor->getTypes($className, $property->getName())) {
            return null;
        }

        $propertyIsCollection = false;
        $allowsNull = false;
        $nativeTypes = [];
        $valueTypes = [];

        $first = true;
        foreach ($propInfoTypeList as $propInfoType) {
            \assert($propInfoType instanceof Type);

            if ($propInfoType->isCollection()) {
                // When there is more than one type, and one of them is a
                // collection, and another is not, just fail and return
                // nothing.
                if (!$first && !$propertyIsCollection) {
                    return null;
                }
                $first = false;
                $propertyIsCollection = true;
                $valueTypes = $propInfoType->getCollectionValueTypes();
            }
        }

        if (!$propertyIsCollection) {
            $valueTypes = $propInfoTypeList;
        }

        if ($valueTypes) {
            foreach ($valueTypes as $subType) {
                \assert($subType instanceof Type);

                $allowsNull = $allowsNull || $subType->isNullable();

                if ($targetClassName = $subType->getClassName()) {
                    $nativeTypes[] = \trim($targetClassName, '\\');
                } else if ($targetBuiltInType = $subType->getBuiltinType()) {
                    $nativeTypes[] = $targetBuiltInType;
                }
            }
        }

        if ($nativeTypes) {
            return new HydratedProperty(
                allowsNull: $allowsNull,
                className: $className,
                collection: $propertyIsCollection,
                name: $propertyName,
                nativeTypes: $nativeTypes,
            );
        }

        return null;
    }
}
