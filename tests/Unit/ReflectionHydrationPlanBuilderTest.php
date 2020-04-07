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

use GeneratedHydrator\Bridge\Symfony\HydrationPlan\ReflectionHydrationPlanBuilder;
use PHPUnit\Framework\TestCase;

final class ReflectionHydrationPlanBuilderTest extends TestCase
{
    /**
     * Data provider
     */
    public static function dataExtractTypesFromDocBlock()
    {
        // Non nullables non collections
        yield ["/** @var \Some\Class */", 'Some\Class', false, false];
        yield ["/** @var \DateTime */", 'DateTime', false, false];

        // Nullable use cases
        yield ["/** @var ?\Some\Class */", 'Some\Class', true, false];
        yield ["/** @var Some\Class|null */", 'Some\Class', true, false];

        // Various collections
        yield ["/** @var \DateTime[] */", 'DateTime', false, true];
        yield ["/** @var ?\DateTimeInterface[] */", 'DateTimeInterface', true, true];

        // Some advanced list types.
        // @todo We parse them correctly, but there is no point in attempting
        //   to guess "list of list of Foo" types, we won't do anything with it.
        // yield ["/** @var list<Foo[]> */", 'Foo', false, true];
        // yield ["/** @var list<string, Foo[]> */", 'Foo', false, true];
        // yield ["/** @var ?list<Foo[]> */", 'Foo', true, true];
        // yield ["/** @var ?list<string, Foo[]> */", 'Foo', true, true];
        // yield ["/** @var null|list<Foo[]> */", 'Foo', true, true];
        // yield ["/** @var null|list<string, Foo[]> */", 'Foo', true, true];
        // yield ["/** @var array<Foo[]> */", 'Foo', false, true];
        // yield ["/** @var array<string, Foo[]> */", 'Foo', false, true];
        // yield ["/** @var ?array<Foo[]> */", 'Foo', true, true];
        // yield ["/** @var ?array<string, Foo[]> */", 'Foo', true, true];
        // yield ["/** @var null|array<Foo[]> */", 'Foo', true, true];
        // yield ["/** @var null|array<string, Foo[]> */", 'Foo', true, true];
    }

    /**
     * @dataProvider dataExtractTypesFromDocBlock
     */
    public function testExtractTypesFromDocBlock($docBlock, string $expected, bool $optional, bool $collection): void
    {
        $types = ReflectionHydrationPlanBuilder::extractTypesFromDocBlock($docBlock);

        self::assertCount(1, $types);
        self::assertSame($expected, $types[0][0]);
        self::assertSame($collection, $types[0][1]);
        self::assertSame($optional, $types[0][2]);
    }

    /**
     * Data provider
     */
    public static function dataExtractTypesFromDocBlockWithScalars()
    {
        // Real use case of failing production code use cases.
        yield ["/** @var array<string, string> */"];
    }

    /**
     * @dataProvider dataExtractTypesFromDocBlockWithScalars
     */
    public function testExtractTypesFromDocBlockWithScalars($docBlock): void
    {
        $types = ReflectionHydrationPlanBuilder::extractTypesFromDocBlock($docBlock);

        self::assertCount(0, $types);
    }

    public function testResolveTypeFromClassPropertyWithFqdn(): void
    {
        self::markTestIncomplete();
    }

    public function testResolveTypeFromClassPropertyWithLocalName(): void
    {
        self::markTestIncomplete();
    }

    public function testResolveTypeFromClassPropertyFromUseStatements(): void
    {
        self::markTestIncomplete();
    }
}
