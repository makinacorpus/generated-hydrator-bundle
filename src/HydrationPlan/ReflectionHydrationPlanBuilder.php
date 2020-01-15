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

use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;

/**
 * Caches the hydration plan
 */
final class ReflectionHydrationPlanBuilder implements HydrationPlanBuilder
{
    /** @var bool */
    private $propertiesAreTyped = false;

    /** @var ?PropertyTypeExtractorInterface */
    private $typeInfoExtractor;

    /** @var bool */
    private $typeInfoExtractorLoaded = false;

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
     * Excluded types
     */
    public static function isTypeBlacklisted(string $type): bool
    {
        return \in_array($type, ['bool', 'float', 'int', 'null', 'string', 'array']);
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
        if ($refType->isBuiltIn()) { // If it's not built-in, it's a class or interface.
            return null;
        }

        return new HydratedProperty($propertyName, $refType->getName(), false, $refType->allowsNull());
    }

    /**
     * From a class name, resolve a class alias.
     *
     * @internal Set to public for unit tests only.
     */
    private static function resolveTypeFromClass(string $className, string $propertyName, string $type, bool $allowUnsafeClassResolution = false): ?string
    {
        $class = new \ReflectionClass($className);

        if ($type === $class->getShortName()) {
            return $class->getName();
        }

        if ($allowUnsafeClassResolution) {
            if ($namespace = $class->getNamespaceName()) {
                // This is wrong because we don't have file use statements.
                // Local classes could be aliased and hidden.
                $candidate = '\\'.$namespace.'\\'.$type;
                if (\class_exists($candidate) || \interface_exists($candidate)) {
                    return $candidate;
                }
            }
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
     * Return an array of type definition arrays from an arbitrary doc block
     *
     * @internal Set to public for unit tests only.
     *
     * @return array[string,bool,bool]
     */
    public static function extractTypesFromDocBlock(string $docBlock): ?array
    {
        // This is where it becomes really ulgy.
        $matches = [];
        if (!\preg_match('/@var\s+([^\s\n@]+)/ums', $docBlock, $matches)) {
            return null;
        }

        $typeStrings = \array_unique(
            \array_filter(
                \array_map(
                    '\trim',
                    \explode('|', $matches[1])
                )
            )
        );

        // If one occurence of 'null' or an unsupported type is found, we can
        // consider the whole as optional, because we will not be able to
        // normalize some variants of it.
        $allAreOptional = false;
        foreach ($typeStrings as $index => $type) {
            if ('null' === $type || 'callable' === $type || 'resource' === $type) {
                unset($typeStrings[$index]);
                $allAreOptional = true;
            }
        }

        $ret = [];
        foreach ($typeStrings as $type) {
            if ($optional = '?' === $type[0]) {
                $type = \substr($type, 1);
            }
            if ($collection = '[]' === \substr($type, -2)) {
                $type = \substr($type, 0, -2);
            }

            // Proceed to a second removal pass now that '[]' and '?' have
            // been stripped.
            if (self::isTypeBlacklisted($type)) {
                continue;
            }

            // Internal type reprensentation with uses a QDN which must be
            // absolute, and unprefixed with '\\'. Else custom class resolver
            // will fail when using CLASS::class constant.
            $type = \trim($type, '\\');

            $ret[] = [$type, $collection, $allAreOptional || $optional];
        }

        return $ret;
    }

    /**
     * Find property type with raw doc block from reflexion
     *
     * @return list<HydratedProperty>
     */
    private function findPropertyWithRawDocBlock(string $className, string $propertyName, \ReflectionProperty $property): array
    {
        if (!$docBlock = $property->getDocComment()) {
            return [];
        }

        // Arbitrary take the first, sorry.
        // We don't support union types yet.
        $ret = [];
        foreach (self::extractTypesFromDocBlock($docBlock) as $array) {
            if ($targetClassName = self::resolveTypeFromClassProperty($className, $propertyName, $array[0])) {
                $ret[] = new HydratedProperty($propertyName, $targetClassName, $array[1], $array[2]);
            }
        }

        return $ret;
    }

    /**
     * Find property type using symfony/property-info.
     *
     * @return list<HydratedProperty>
     */
    private function findPropertyWithPropertyInfo(string $className, string $propertyName, \ReflectionProperty $property): ?array
    {
        if (!$typeInfoExtractor = $this->getTypeInfoExtractor()) {
            return null;
        }
        if (!$types = $typeInfoExtractor->getTypes($className, $property->getName())) {
            return null;
        }

        $ret = [];
        foreach ($types as $type) {
            \assert($type instanceof Type);
            if ($type->isCollection()) {
                if ($targetClassName = $type->getCollectionValueType()->getClassName()) {
                    $ret[] = new HydratedProperty($propertyName, $targetClassName, true, true);
                }
            } else if ($targetClassName = $type->getClassName()) {
                $ret[] = new HydratedProperty($propertyName, $targetClassName, false, $type->isNullable());
            }
        }

        return $ret;
    }

    /**
     * Parse property definition
     *
     * @return list<HydratedProperty>
     *   Multiple occurence of the same property can exist, if more than
     *   one allowed type was found.
     */
    private function findPropertyDefinition(string $className, string $propertyName, \ReflectionProperty $property): array
    {
        if ($ret = $this->findPropertyWithReflection($className, $propertyName, $property)) {
            return [$ret];
        }
        if ($ret = $this->findPropertyWithRawDocBlock($className, $propertyName, $property)) {
            return $ret;
        }
        if ($ret = $this->findPropertyWithPropertyInfo($className, $propertyName, $property)) {
            return $ret;
        }
        return [];
    }

    /**
     * Recursion to find all parent and traits included properties.
     *
     * @return list<\ReflectionProperty>
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
     * Build hydration plan
     *
     * @param list<string> $propertyBlackList
     *   Properties to ignore
     */
    public function build(string $className, array $propertyBlackList = []): HydrationPlan
    {
        $ref = new \ReflectionClass($className);

        $properties = [];
        foreach ($this->findAllProperties($ref) as $property) {
            \assert($property instanceof \ReflectionProperty);
            foreach ($this->findPropertyDefinition($className, $property->getName(), $property) as $definition) {
                $properties[] = $definition;
                break; // We can only keep one, sorry.
            }
        }

        return new DefaultHydrationPlan($className, $properties);
    }
}
