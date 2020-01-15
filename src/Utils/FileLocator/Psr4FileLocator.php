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

namespace GeneratedHydrator\Bridge\Symfony\Utils\FileLocator;

use CodeGenerationUtils\FileLocator\FileLocatorInterface;
use GeneratedHydrator\Bridge\Symfony\Utils\Psr4Configuration;

/**
 * @see \GeneratedHydrator\Bridge\Symfony\Utils\Psr4Configuration
 */
final class Psr4FileLocator implements FileLocatorInterface
{
    /** @var string */
    private $psr4NamespacePrefix;

    /** @var string */
    private $psr4DirectoryRoot;

    /**
     * @param string $generatedClassesDirectory
     *
     * @throws \CodeGenerationUtils\Exception\InvalidGeneratedClassesDirectoryException
     */
    public function __construct(string $psr4DirectoryRoot, string $psr4NamespacePrefix)
    {
        $this->psr4NamespacePrefix = $psr4NamespacePrefix;
        $this->psr4DirectoryRoot = \str_replace('\\', '/', \rtrim($psr4DirectoryRoot, '/\\'));
    }

    /**
     * {@inheritDoc}
     */
    public function getGeneratedClassFileName(string $className) : string
    {
        $classNameSuffix = Psr4Configuration::getClassSuffixInNamespace($className, $this->psr4NamespacePrefix);

        return $this->psr4DirectoryRoot . '/' . \str_replace('\\', '/', $classNameSuffix) . '.php';
    }
}
