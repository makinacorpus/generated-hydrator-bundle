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

use GeneratedHydrator\Bridge\Symfony\Utils\Parser\TypeParser;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;

/**
 * Caches the hydration plan
 */
final class ReflectionHydrationPlanBuilder implements HydrationPlanBuilder
{
    private bool $propertiesAreTyped = false;
    private ?PropertyTypeExtractorInterface $typeInfoExtractor = null;
    private bool $typeInfoExtractorLoaded = false;
    private bool $typeInfoExtractorEnabled = true;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $this->propertiesAreTyped = (\version_compare(PHP_VERSION, '7.4.0') >= 0);
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
     * Scalar types.
     */
    public static function isTypePrimitive(string $type): bool
    {
        return 'bool' === $type || 'float' === $type || 'int' === $type || 'string' === $type;
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
            // @todo Do this better. Use class_implements() or such to find
            //   \Traversable types in parenting tree.
            'Traversable' === $type || 'Generator' === $type || 'Iterator' === $type
        ;
    }

    /**
     * Excluded types
     */
    public static function isTypeBlacklisted(string $type): bool
    {
        return self::isTypePrimitive($type) || self::isCollectionType($type) || 'null' === $type || 'callable' === $type;
    }

    /**
     * @internal For unit testing purpose only.
     * @codeCoverageIgnore
     */
    public function disablePropertyTypeReflection(): void
    {
        $this->propertiesAreTyped = false;
    }

    /**
     * @internal For unit testing purpose only.
     * @codeCoverageIgnore
     */
    public function disableTypeInfoExtractor()
    {
        $this->typeInfoExtractorEnabled = false;
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
        if (!$this->typeInfoExtractorEnabled) {
            return null;
        }

        if (!$this->typeInfoExtractorLoaded && !$this->typeInfoExtractor) {
            $this->typeInfoExtractorLoaded = true;
            $this->typeInfoExtractor = self::createDefaultTypeInfoExtractor();
        }

        return $this->typeInfoExtractor;
    }

    /**
     * Find property type using PHP 7.4+ reflection and property type.
     */
    private function findPropertyWithReflection(string $className, string $propertyName, \ReflectionProperty $property): ?HydratedProperty
    {
        if (!$this->propertiesAreTyped || !$property->hasType()) {
            return null;
        }

        if (!$refType = $property->getType()) {
            return null;
        }

        $typeName = $refType->getName();

        // Drop collection types, in order to let PHP docblock parser to find
        // target collection value type.
        if (self::isCollectionType($typeName)) {
            return null;
        }

        $property = new HydratedProperty();
        $property->allowsNull = $refType->allowsNull();
        $property->builtIn = $refType->isBuiltIn();
        $property->className = $typeName;
        $property->collection = false;
        $property->name = $propertyName;
        $property->union = false; // @todo PHP8 will have this.

        return $property;
    }

    /**
     * Return an array of type definition arrays from an arbitrary doc block.
     *
     * @internal Set to public for unit tests only.
     */
    public static function extractTypesFromDocBlock(string $docBlock): ?HydratedProperty
    {
        // This is where it becomes really ulgy.
        $matches = [];
        if (!\preg_match('/@var\s+([^\*\n@]+)/ums', $docBlock, $matches)) {
            return [];
        }

        try {
            if (!$type = TypeParser::parse($matches[1])) {
                // If we were unable to find any type information, property-info
                // will not succeeed either, so drop from here.
                return HydratedProperty::empty();
            }
        } catch (\Exception $e) {
            // Be silent when a PHP docblock contains typos.
            return null;
        }

        $firstTypeFound = null;
        $propertyIsCollection = false;

        // We do not handle type recursivity, only base type matters here,
        // so we will attempt to find the most generic type of the list.
        // @todo current code will only take the first one.
        if ($type->isCollection && $type->valueType) {
            $propertyIsCollection = true;

            foreach ($type->valueType->internalTypes as $phpType) {
                // Internal type reprensentation with uses a QDN which must be
                // absolute, and unprefixed with '\\'. Else custom class resolver
                // will fail when using CLASS::class constant.
                $phpType = \trim($phpType, '\\');

                if (self::isTypeBlacklisted($phpType)) {
                    if (!$firstTypeFound && self::isTypePrimitive($phpType)) {
                        $firstTypeFound = $phpType;
                    }
                    continue;
                }

                // Stop on first.
                // @todo Handle union types correctly.
                $property = new HydratedProperty();
                $property->allowsNull = $type->isNullable;
                $property->builtIn = false;
                $property->className = $phpType;
                $property->collection = true;
                $property->union = $type->isUnion();

                return $property;
            }
        } else {
            foreach ($type->internalTypes as $phpType) {
                // Internal type reprensentation with uses a QDN which must be
                // absolute, and unprefixed with '\\'. Else custom class resolver
                // will fail when using CLASS::class constant.
                $phpType = \trim($phpType, '\\');

                if (self::isTypeBlacklisted($phpType)) {
                    if (!$firstTypeFound && self::isTypePrimitive($phpType)) {
                        $firstTypeFound = $phpType;
                    }
                    continue;
                }

                $property = new HydratedProperty();
                $property->allowsNull = $type->isNullable;
                $property->builtIn = false;
                $property->className = $phpType;
                $property->collection = false;
                $property->union = $type->isUnion();

                return $property;
            }
        }

        if (!$firstTypeFound) {
            return null;
        }

        // If we found at least a scalar type, return that early and do not
        // let the very slow property-info component lookup for a type.
        $property = new HydratedProperty();
        $property->allowsNull = $type->isNullable;
        $property->builtIn = true;
        $property->className = $firstTypeFound;
        $property->collection = $propertyIsCollection;
        $property->union = false;

        return $property;
    }

    /**
     * From a class name, resolve a class alias.
     */
    private static function resolveTypeFromClass(string $className, string $propertyName, string $type): ?string
    {
        try {
            $class = new \ReflectionClass($className);
            if ($type === $class->getShortName()) {
                // Class is in the root namespace.
                return $class->getName();
            }
        } catch (\Throwable $e) {
            // Reflection did not find class.
            return null;
        }
        return null;
    }

    /**
     * From a reflection property, resolve a class alias.
     *
     * @internal Set to public for unit tests only.
     */
    public static function resolveTypeFromClassProperty(string $className, string $propertyName, string $type, bool $allowUnsafeClassResolution = false): ?string
    {
        if (!$type) {
            return null; // Empty type.
        }
        if ('\\' === $type[0]) {
            return $type; // FQDN
        }
        return self::resolveTypeFromClass($className, $propertyName, $type, $allowUnsafeClassResolution);
    }

    /**
     * Find property type with raw doc block from reflexion.
     */
    private function findPropertyWithRawDocBlock(string $className, string $propertyName, \ReflectionProperty $property):  ?HydratedProperty
    {
        if (!$docBlock = $property->getDocComment()) {
            return null;
        }
        if (!$property = self::extractTypesFromDocBlock($docBlock)) {
            return null;
        }

        if (!$property->builtIn) {
            $targetClassName = self::resolveTypeFromClassProperty($className, $propertyName, $property->className);

            if (!$targetClassName) {
                // Class name resolution was incomplete an unsafe, do not let
                // incomplete types pass, and let the property-info component
                // find the right class.
                return null;
            }

            $property->className = $targetClassName;
        }

        return $property;
    }

    /**
     * Find property type using symfony/property-info.
     */
    private function findPropertyWithPropertyInfo(string $className, string $propertyName, \ReflectionProperty $property):  ?HydratedProperty
    {
        if (!$typeInfoExtractor = $this->getTypeInfoExtractor()) {
            return null;
        }
        if (!$types = $typeInfoExtractor->getTypes($className, $property->getName())) {
            return null;
        }

        foreach ($types as $type) {
            \assert($type instanceof Type);

            if ($type->isCollection()) {
                if ($targetClassName = $type->getCollectionValueType()->getClassName()) {
                    $property = new HydratedProperty();
                    $property->allowsNull = true;
                    $property->builtIn = false;
                    $property->className = $targetClassName;
                    $property->collection = true;
                    $property->union = false;
    
                    return $property;
                }
            }

            if ($targetClassName = $type->getClassName()) {
                $property = new HydratedProperty();
                $property->allowsNull = $type->isNullable();
                $property->builtIn = false;
                $property->className = $targetClassName;
                $property->collection = false;
                $property->union = false;

                return $property;
            }
        }

        return null;
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
        $property =
            $this->findPropertyWithReflection($className, $propertyName, $property) ??
            $this->findPropertyWithRawDocBlock($className, $propertyName, $property) ??
            $this->findPropertyWithPropertyInfo($className, $propertyName, $property)
        ;

        if ($property) {
            $property->name = $propertyName;
        }

        return $property;
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
                // Ignore builtIn types, for now. PHP will handle them
                // natively as soon as they are typed.
                if (!$definition->builtIn) {
                    $properties[] = $definition;
                }
            }
        }

        return new DefaultHydrationPlan($className, $properties);
    }
}
