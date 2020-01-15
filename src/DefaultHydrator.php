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

use GeneratedHydrator\Configuration;
use Zend\Hydrator\HydratorInterface;

/**
 * Provide a front-end for hydrating objects.
 */
final class DefaultHydrator implements Hydrator
{
    /** Generates hydrator classes into Symfony cache directory */
    const MODE_CACHE = 'cache';

    /** Generates hydrator classes into your app namespace */
    const MODE_PSR4 = 'psr4';

    /** @var string */
    private $generatedClassesTargetDir;

    /** @var string */
    private $defaultMode = self::MODE_CACHE;

    /** @var array<string, HydratorInterface> */
    private $hydrators = [];

    /** @var array<string, scalar> */
    private $userConfiguration = [];

    /** @var array<string, \ReflectionClass> */
    private $reflectionClasses = [];

    /**
     * Default constructor
     */
    public function __construct(string $generatedClassesTargetDir, array $userConfiguration = [], string $defaultMode = self::MODE_CACHE)
    {
        $this->generatedClassesTargetDir = $generatedClassesTargetDir;
        $this->userConfiguration = $userConfiguration; // @todo validate it?
        $this->defaultMode = $defaultMode;
    }

    /**
     * Create hydrator configuration
     */
    private function createConfiguration(string $className): Configuration
    {
        $userConfiguration = \array_replace([
            'auto_generate_proxies' => true,
            'class_name' => null,
            'class_namespace' => null,
            'target_dir' => $this->generatedClassesTargetDir,
        ], $this->userConfiguration[$className] ?? []);

        // @todo
        //    - global mode (cache or psr4)
        //    - create symfony configuration with documentation
        //    - create default class name inflectors and file locators depending upon mode
        //    - if psr4 et no configuration, create a configuration with mode
        //    - basic bundle unit tests
        //    - psr4 mode heavy unit tests
        //    - coverage tests
        //    - configuration per namespace (e.g. "App\Domain\Model\*") instead of per class
        //    - test hydrate/extract/createAndHydrate
        // @todo later
        //    - instantiatior for classes
        //    - nested objects hydration (normalization)

        $configuration = new Configuration($className);
        $configuration->setGeneratedClassesTargetDir($userConfiguration['target_dir']);
        if ($value = ($userConfiguration['auto_generate_proxies'] ?? null)) {
            $configuration->setAutoGenerateProxies($value);
        }
        if ($value = ($userConfiguration['class_name'] ?? null)) {
            $configuration->setHydratedClassName($value);
        }
        if ($value = ($userConfiguration['class_namespace'] ?? null)) {
            $configuration->setGeneratedClassesNamespace($value);
        }

        return $configuration;
    }

    /**
     * Create hydrator for class
     */
    private function createHydrator(string $className): HydratorInterface
    {
        $hydratorClassName = $this
            ->createConfiguration($className)
            ->createFactory()
            ->getHydratorClass() // @todo getHydrator() directly in future versions
        ;

        return new $hydratorClassName();
    }

    /**
     * Get hydrator for class
     */
    private function getHydrator(string $className): HydratorInterface
    {
        return $this->hydrators[$className] ?? (
            $this->hydrators[$className] = $this->createHydrator($className)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(object $object, array $values): void
    {
        $this->getHydrator(\get_class($object))->hydrate($values, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function createAndHydrate(string $className, array $values): object
    {
        $reflection = $this->reflectionClasses[$className] ?? (
            $this->reflectionClasses[$className] = new \ReflectionClass($className)
        );
        \assert($reflection instanceof \ReflectionClass);

        $object = $reflection->newInstanceWithoutConstructor();
        $this->hydrate($object, $values);

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function extract(object $object): array
    {
        return $this->getHydrator(\get_class($object))->extract($object);
    }
}
