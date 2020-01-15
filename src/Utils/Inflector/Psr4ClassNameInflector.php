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

namespace GeneratedHydrator\Bridge\Symfony\Utils\Inflector;

use CodeGenerationUtils\Inflector\ClassNameInflectorInterface;

/**
 * @see \GeneratedHydrator\Bridge\Symfony\Utils\Psr4Configuration
 */
final class Psr4ClassNameInflector implements ClassNameInflectorInterface
{
    /** @var string */
    private $psr4NamespacePrefix;

    /** @var ?string */
    private $namespaceInfix;

    /**
     * Default constructor.
     */
    public function __construct(string $psr4NamespacePrefix, ?string $namespaceInfix = self::NAMESPACE_INFIX_DEFAULT)
    {
        $this->psr4NamespacePrefix = $psr4NamespacePrefix;
        $this->namespaceInfix = $namespaceInfix;
    }

    /**
     * {@inheritdoc}
     */
    public function getGeneratedClassName(string $className, array $options = []): string
    {
        // @todo
        //   - check if class in namespace
        //   - remove namespace, add infix, restore trailing namespace
        //   - add class name suffix, configurable ?
        //   - if not in the same namespace, raise error?
        throw new \Exception("Not implemented yet");
    }

    /**
     * {@inheritdoc}
     */
    public function getUserClassName(string $className): string
    {
        throw new \RuntimeException("%s::%s is not implemented.", __CLASS__, __METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function isGeneratedClassName(string $className): bool
    {
        throw new \RuntimeException("%s::%s is not implemented.", __CLASS__, __METHOD__);
    }
}
