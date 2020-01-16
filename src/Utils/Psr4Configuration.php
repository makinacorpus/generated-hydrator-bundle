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

use CodeGenerationUtils\GeneratorStrategy\GeneratorStrategyInterface;
use CodeGenerationUtils\Inflector\ClassNameInflectorInterface;
use GeneratedHydrator\Configuration;

/**
 * We need to override the configuration class to ensure that
 * getGeneratorStrategy() will always be called lazyly.
 *
 * If we dont, we must run setGeneratorStrategy() before knowing if the
 * hydrator already exists or not, which will create an AST as some point
 * and doing a few manipulations over it: we do NOT want this.
 */
class Psr4Configuration extends Configuration
{
    private $psr4Factory;

    public function setPsr4Factory(Psr4Factory $psr4Factory): void
    {
        $this->psr4Factory = $psr4Factory;
    }

    /**
     * {@inheritdoc}
     */
    public function getGeneratorStrategy() : GeneratorStrategyInterface
    {
        if ($this->generatorStrategy === null) {
            $this->generatorStrategy = $this->psr4Factory->getGeneratorStrategy();
        }

        return $this->generatorStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNameInflector() : ClassNameInflectorInterface
    {
        if ($this->classNameInflector === null) {
            $this->classNameInflector = $this->psr4Factory->getClassNameInflector();
        }

        return $this->classNameInflector;
    }
}
