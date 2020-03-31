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

namespace GeneratedHydrator\Bridge\Symfony\Utils;

use CodeGenerationUtils\FileLocator\FileLocatorInterface;
use CodeGenerationUtils\GeneratorStrategy\FileWriterGeneratorStrategy;
use CodeGenerationUtils\GeneratorStrategy\GeneratorStrategyInterface;
use CodeGenerationUtils\Inflector\ClassNameInflectorInterface;
use GeneratedHydrator\Bridge\Symfony\Utils\FileLocator\Psr4FileLocator;
use GeneratedHydrator\Bridge\Symfony\Utils\Inflector\Psr4ClassNameInflector;

/**
 * Configures components for PSR-4 class naming and file locator.
 *
 * PSR-4 class name inflector, this needs your generated target class
 * directory to be within your application source; consider your application
 * source folder to be:
 *
 *   "%kernel.project_dir%/src"
 *
 * Where the namespace is:
 *
 *   "MyVendor\\MyApp\\"
 *
 * And the namespace where to write your hydrators:
 *
 *   "MyVendor\\MyApp\\Hydrator\\"
 *
 * Then you must set as target directory:
 *
 *   "%kernel.project_dir%/src/Hydrator"
 *
 * Then consider the following entity class:
 *
 *   "MyVendor\\MyApp\\Model\\SomeEntity"
 *
 * The hydrator class will be:
 *
 *   "MyVendor\\MyApp\\Hydrator\\Model\\SomeEntity"
 */
final class Psr4Factory
{
    const NAMESPACE_INFIX_DEFAULT = 'Hydrator';

    /** @var string */
    private $psr4DirectoryRoot;

    /** @var string */
    private $psr4NamespacePrefix;

    /** @var ?string */
    private $namespaceInfix;

    /** @var ?FileLocatorInterface */
    private $fileLocator;

    /** @var ?ClassNameInflectorInterface */
    private $nameInflector;

    /** @var GeneratorStrategyInterface */
    private $generatorStrategy;

    /**
     * Default constructor
     */
    public function __construct(string $psr4DirectoryRoot, string $psr4NamespacePrefix, ?string $namespaceInfix = self::NAMESPACE_INFIX_DEFAULT)
    {
        $this->psr4DirectoryRoot = $psr4DirectoryRoot;
        $this->psr4NamespacePrefix = $psr4NamespacePrefix;
        $this->namespaceInfix = $namespaceInfix;
    }

    /**
     * From the given namespace prefix, find class name suffix (after the prefix).
     */
    public static function getClassSuffixInNamespace(string $className, string $namespacePrefix): string
    {
        $className = \trim($className, '\\');

        $namespaceLength = \strlen($namespacePrefix);
        if (\substr($className, 0, $namespaceLength) !== $namespacePrefix) {
            // For classes that don't belong to our namespace, use the FQDN
            // as class suffix.
            return $className;
        }

        return \substr($className, $namespaceLength + 1);
    }

    /**
     * Get class name inflector
     */
    public function getClassNameInflector(): ClassNameInflectorInterface
    {
        return $this->nameInflector ?? (
            $this->nameInflector = new Psr4ClassNameInflector($this->psr4NamespacePrefix, $this->namespaceInfix)
        );
    }

    /**
     * Get file locator
     */
    public function getFileLocator(): FileLocatorInterface
    {
        return $this->fileLocator ?? (
            $this->fileLocator = new Psr4FileLocator($this->psr4DirectoryRoot, $this->psr4NamespacePrefix)
        );
    }

    /**
     * Get generator strategy
     */
    public function getGeneratorStrategy(): GeneratorStrategyInterface
    {
        return $this->generatorStrategy ?? (
            $this->generatorStrategy = new FileWriterGeneratorStrategy(
                $this->getFileLocator()
            )
        );
    }
}
