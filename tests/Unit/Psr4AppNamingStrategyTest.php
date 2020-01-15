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

namespace GeneratedHydrator\Bridge\Symfony\Tests\Unit;

use GeneratedHydrator\Bridge\Symfony\Utils\Psr4Configuration;
use PHPUnit\Framework\TestCase;

final class Psr4AppNamingStrategyTest extends TestCase
{
    public function testNormalUseCase(): void
    {
        $configuration = new Psr4Configuration('/var/www/my-app/src', 'MyVendor\\MyApp');
        $classNameInflector = $configuration->getClassNameInflector();
        $fileLocator = $configuration->getFileLocator();

        $entityClass = 'MyVendor\\MyApp\\Domain\\Model\\SomeEntity';

        $hydratorClassName = $classNameInflector->getGeneratedClassName($entityClass);
        self::assertSame('MyVendor\\MyApp\\Hydrator\\Domain\\Model\\SomeEntityHydrator', $hydratorClassName);

        $filename = $fileLocator->getGeneratedClassFileName($hydratorClassName);
        self::assertSame('/var/www/my-app/src/Hydrator/Domain/Model/SomeEntityHydrator.php', $filename);
    }

    public function testWithDiffentSuffixAndInfix(): void
    {
        $configuration = new Psr4Configuration('/var/www/my-app/src', 'MyVendor\\MyApp', 'Bar');
        $classNameInflector = $configuration->getClassNameInflector();
        $fileLocator = $configuration->getFileLocator();

        $entityClass = 'MyVendor\\MyApp\\Domain\\Model\\SomeEntity';

        $hydratorClassName = $classNameInflector->getGeneratedClassName($entityClass);
        self::assertSame('MyVendor\\MyApp\\Bar\\Domain\\Model\\SomeEntityHydrator', $hydratorClassName);

        $filename = $fileLocator->getGeneratedClassFileName($hydratorClassName);
        self::assertSame('/var/www/my-app/src/Bar/Domain/Model/SomeEntityHydrator.php', $filename);
    }

    public function testWithNoInfix(): void
    {
        $configuration = new Psr4Configuration('/var/www/my-app/src', 'MyVendor\\MyApp', null);
        $classNameInflector = $configuration->getClassNameInflector();
        $fileLocator = $configuration->getFileLocator();

        $entityClass = 'MyVendor\\MyApp\\Domain\\Model\\SomeEntity';

        $hydratorClassName = $classNameInflector->getGeneratedClassName($entityClass);
        self::assertSame('MyVendor\\MyApp\\Domain\\Model\\SomeEntityHydrator', $hydratorClassName);

        $filename = $fileLocator->getGeneratedClassFileName($hydratorClassName);
        self::assertSame('/var/www/my-app/src/Domain/Model/SomeEntityHydrator.php', $filename);
    }
}
