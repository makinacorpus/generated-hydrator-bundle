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
        return \in_array($type, [
            // PHP native types.
            'bool', 'float', 'int', 'null', 'string', 'array',
            // Commonly used list types.
            'iterable', 'list', 'Traversable', 'Generator',
        ]);
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
    public static function extractTypesFromDocBlock(string $docBlock, bool &$stop = false): array
    {
        // This is where it becomes really ulgy.
        $matches = [];
        if (!\preg_match('/@var\s+([^\*\n@]+)/ums', $docBlock, $matches)) {
            return [];
        }

        $ret = [];
        try {
            if (!$type = TypeParser::parse($matches[1])) {
                $stop = true; // If we cannot parse it, DocBlock parser won't either.

                return $ret;
            }
        } catch (\InvalidArgumentException $e) {
            $stop = true; // If we cannot parse it, DocBlock parser won't either.

            // Be silent when a PHP docbock contains typos.
            return $ret;
        }

        $hasScalarType = false;

        // We do not handle type recursivity, only base type matters here,
        // so we will attempt to find the most generic type of the list.
        // @todo current code will only take the first one.
        if ($type->isCollection && $type->valueType) {
            foreach ($type->valueType->internalTypes as $phpType) {
                // Internal type reprensentation with uses a QDN which must be
                // absolute, and unprefixed with '\\'. Else custom class resolver
                // will fail when using CLASS::class constant.
                $phpType = \trim($phpType, '\\');

                if (!self::isTypeBlacklisted($phpType)) {
                    $ret[] = [$phpType, $type->isCollection, $type->isNullable];
                } else {
                    $hasScalarType = true;
                }
            }
        } else {
            foreach ($type->internalTypes as $phpType) {
                // Internal type reprensentation with uses a QDN which must be
                // absolute, and unprefixed with '\\'. Else custom class resolver
                // will fail when using CLASS::class constant.
                $phpType = \trim($phpType, '\\');

                if (!self::isTypeBlacklisted($phpType)) {
                    $ret[] = [$phpType, $type->isCollection, $type->isNullable];
                } else {
                    $hasScalarType = true;
                }
            }
        }

        if ($hasScalarType) {
            $stop = true; // Do not fallback to DocBlock parser.
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
    private function findPropertyWithPropertyInfo(string $className, string $propertyName, \ReflectionProperty $property): array
    {
        if (!$typeInfoExtractor = $this->getTypeInfoExtractor()) {
            return [];
        }
        if (!$types = $typeInfoExtractor->getTypes($className, $property->getName())) {
            return [];
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
        $stop = false;
        if ($ret = $this->findPropertyWithRawDocBlock($className, $propertyName, $property, $stop)) {
            return $ret;
        }
        if ($stop) {
            return [];
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
