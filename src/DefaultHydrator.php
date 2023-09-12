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
use Laminas\Hydrator\HydratorInterface;

/**
 * Provide a front-end for hydrating objects.
 */
final class DefaultHydrator implements Hydrator
{
    /** @var array<string, HydratorInterface> */
    private array $hydrators = [];
    /** @var array<string, \ReflectionClass> */
    private array $reflectionClasses = [];

    public function __construct(
        private string $generatedClassesTargetDir,
        /** @var array<string, scalar> */
        private array $userConfiguration = []
    ) {}

    private function getHydrator(string $className): HydratorInterface
    {
        return $this->hydrators[$className] ?? ($this->hydrators[$className] = $this->createHydrator($className));
    }

    private function createHydrator(string $className): HydratorInterface
    {
        return $this->getHydratorConfiguration($className)->createFactory()->getHydrator();
    }

    private function getHydratorConfiguration(string $className): Configuration
    {
        $userConfiguration = \array_replace([
            'mode' => $this->defaultMode,
            'auto_generate_proxies' => true,
            'class_name' => null,
            'class_namespace' => null,
            'target_dir' => $this->generatedClassesTargetDir,
        ], $this->userConfiguration[$className] ?? []);

        $configuration = new Configuration($className);
        $configuration->setGeneratedClassesTargetDir($userConfiguration['target_dir']);

        // Let those values override the default one above.
        if ($value = ($userConfiguration['auto_generate_proxies'] ?? null)) {
            $configuration->setAutoGenerateProxies($value);
        }
        if ($value = ($userConfiguration['class_name'] ?? null)) {
            $configuration->setHydratedClassName($value);
        }
        if ($value = ($userConfiguration['class_namespace'] ?? null)) {
            $this->configuration->setGeneratedClassesNamespace($value);
        }

        return $this->configuration = $configuration;
    }

    /**
     * Force hydrator regeneration if dumped as a file.
     */
    public function regenerateHydrator(string $className): array
    {
        $configuration = $this->getHydratorConfiguration($className);
        $targetClassName = $configuration->getClassNameInflector()->getGeneratedClassName($className);
        $targetFileName = '(in cache)';

        /*
         * @todo find a way to do this properly, we can't do it after we
         *   generated the classname, because class name generation will
         *   create the class and the autoload will register it, then,
         *   we're doomed, becaue the getHydratorClass() won't life a
         *   finger since the class exists in the PHP side.
         *
        if (\file_exists($targetFileName)) {
            if (!@\unlink($targetFileName)) {
                throw new \RuntimeException(\sprintf("Could not delete file: %s", $targetFileName));
            }
        }
         */

        $configuration->createFactory()->getHydratorClass();

        return ['class' => $targetClassName, 'filename' => $targetFileName];
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(object $object, array $values, ?string $context = null): void
    {
        $this->getHydrator(\get_class($object))->hydrate($values, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function createAndHydrate(string $className, array $values, ?string $context = null): object
    {
        $reflection = $this->reflectionClasses[$className] ?? ($this->reflectionClasses[$className] = new \ReflectionClass($className));
        \assert($reflection instanceof \ReflectionClass);

        $object = $reflection->newInstanceWithoutConstructor();
        $this->hydrate($object, $values);

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function extract(object $object, ?string $context = null): array
    {
        return $this->getHydrator(\get_class($object))->extract($object);
    }
}
