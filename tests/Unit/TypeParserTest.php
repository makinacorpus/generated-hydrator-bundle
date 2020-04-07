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

use PHPUnit\Framework\TestCase;
use GeneratedHydrator\Bridge\Symfony\Utils\Parser\TypeParser;

final class TypeParserTest extends TestCase
{
    public function testCannotStartWithPipe(): void
    {
        self::expectExceptionMessage("Type declaration cannot start with '|' at position #0");
        TypeParser::parse('|Foo');
    }

    public function testCannotDeclareGenericityAfterArray(): void
    {
        self::markTestSkipped("Fix this behaviour");

        self::expectExceptionMessage("Cannot open genericity '<' after '[]' declaration at position #5");
        TypeParser::parse('Foo[]<Bar>');
    }

    public function testCannotStartWithOpeningTag(): void
    {
        self::expectExceptionMessage("Type declaration cannot start with '<' at position #1");
        TypeParser::parse(' <Foo');
    }

    public function testCannotStartWithClosingTag(): void
    {
        self::expectExceptionMessage("Type declaration cannot start with '>' at position #2");
        TypeParser::parse('  >Foo');
    }

    public function testCannotCloseNonOpenedTag(): void
    {
        self::expectExceptionMessage("Non opened type closing '>' at position #3");
        TypeParser::parse('Foo>');
    }

    public function testCannotStartWithComma(): void
    {
        self::expectExceptionMessage("Type declaration cannot start with ',' at position #0");
        TypeParser::parse(',Foo>');
    }

    public function testCannotPutCommaWithoutGeneric(): void
    {
        self::expectExceptionMessage("Misplaced ',' at position #3");
        TypeParser::parse('Foo,');
    }

    public function testCannotPutTwoComma(): void
    {
        self::expectExceptionMessage("Misplaced ',' at position #13");
        TypeParser::parse('array<Foo,Bar,Baz>');
    }

    public function testCannotPutCommaWithoutKeyType(): void
    {
        self::markTestSkipped("Fix this behaviour");

        self::expectExceptionMessage("Misplaced ',' at position #6");
        TypeParser::parse('array<,Foo>');
    }

    public function testCannotPutCommaWithoutValueType(): void
    {
        self::markTestSkipped("Fix this behaviour");

        self::expectExceptionMessage("Misplaced ',' at position #9");
        TypeParser::parse('array<Foo,>');
    }

    public function testTokenizeType(): void
    {
        $type = TypeParser::parse('  \DateTimeInterface  ');

        self::assertSame(['\DateTimeInterface'], $type->internalTypes);
        self::assertFalse($type->isNullable);
        self::assertFalse($type->isGeneric);
        self::assertFalse($type->isCollection);
    }

    public function dataTokenizeNullableType(): array
    {
        return [
            ['?string'],
            ['null|string'],
        ];
    }

    /**
     * @dataProvider dataTokenizeNullableType
     */
    public function testTokenizeNullableType(string $typeExpression): void
    {
        $type = TypeParser::parse($typeExpression);

        self::assertSame(['string'], $type->internalTypes);
        self::assertTrue($type->isNullable);
        self::assertFalse($type->isGeneric);
        self::assertFalse($type->isCollection);
    }

    public function testTokenizeUnionType(): void
    {
        $type = TypeParser::parse('int|string|\DateTime');

        self::assertSame(['int', 'string', '\DateTime'], $type->internalTypes);
        self::assertFalse($type->isNullable);
        self::assertFalse($type->isGeneric);
        self::assertFalse($type->isCollection);
    }

    public function testTokenizeNullableUnionType(): void
    {
        $type = TypeParser::parse('null|string|int');

        self::assertSame(['string', 'int'], $type->internalTypes);
        self::assertTrue($type->isNullable);
        self::assertFalse($type->isGeneric);
        self::assertFalse($type->isCollection);
    }

    public function dataTokenizeArray(): array
    {
        return [
            ['array<Foo>'],
            ['Foo[]'],
            ['list<Foo>'],
            ['iterable<Foo>'],
            ['\Traversable<Foo>'],
        ];
    }

    /**
     * @dataProvider dataTokenizeArray
     */
    public function testTokenizeArray(string $typeExpression): void
    {
        $type = TypeParser::parse($typeExpression);

        self::assertFalse($type->isNullable);
        self::assertTrue($type->isGeneric);
        self::assertTrue($type->isCollection);

        self::assertNull($type->keyType);

        self::assertSame(['Foo'], $type->valueType->internalTypes);
        self::assertFalse($type->valueType->isNullable);
        self::assertFalse($type->valueType->isGeneric);
        self::assertFalse($type->valueType->isCollection);
    }

    public function dataTokenizeNullableArray(): array
    {
        return [
            ['null|array<Foo>'],
            ['null|Foo[]'],
            ['array<Foo>|null'],
            // ['Foo[]|null'], // @todo Fix this one.
            ['?array<Foo>'],
            ['?Foo[]'],
        ];
    }

    /**
     * @dataProvider dataTokenizeNullableArray
     */
    public function testTokenizeNullableArray(string $typeExpression): void
    {
        $type = TypeParser::parse($typeExpression);

        self::assertTrue($type->isNullable);
        self::assertTrue($type->isGeneric);
        self::assertTrue($type->isCollection);

        self::assertNull($type->keyType);

        self::assertSame(['Foo'], $type->valueType->internalTypes);
        self::assertFalse($type->valueType->isNullable);
        self::assertFalse($type->valueType->isGeneric);
        self::assertFalse($type->valueType->isCollection);
    }

    public function testTokenizeArrayWithKey(): void
    {
        $type = TypeParser::parse('array <string, Foo>');

        self::assertSame(['array'], $type->internalTypes);
        self::assertFalse($type->isNullable);
        self::assertTrue($type->isGeneric);
        self::assertTrue($type->isCollection);

        self::assertSame(['string'], $type->keyType->internalTypes);
        self::assertFalse($type->keyType->isNullable);
        self::assertFalse($type->keyType->isGeneric);
        self::assertFalse($type->keyType->isCollection);

        self::assertSame(['Foo'], $type->valueType->internalTypes);
        self::assertFalse($type->valueType->isNullable);
        self::assertFalse($type->valueType->isGeneric);
        self::assertFalse($type->valueType->isCollection);
    }

    public function testTokenizeArrayWithUnionKeyType(): void
    {
        $type = TypeParser::parse('array <int|string, Foo>');

        self::assertSame(['array'], $type->internalTypes);
        self::assertFalse($type->isNullable);
        self::assertTrue($type->isGeneric);
        self::assertTrue($type->isCollection);

        self::assertSame(['int', 'string'], $type->keyType->internalTypes);
        self::assertFalse($type->keyType->isNullable);
        self::assertFalse($type->keyType->isGeneric);
        self::assertFalse($type->keyType->isCollection);

        self::assertSame(['Foo'], $type->valueType->internalTypes);
        self::assertFalse($type->valueType->isNullable);
        self::assertFalse($type->valueType->isGeneric);
        self::assertFalse($type->valueType->isCollection);
    }

    public function testTokenizeArrayWithUnionValueType(): void
    {
        $type = TypeParser::parse('array <string, Foo | Bar>');

        self::assertSame(['array'], $type->internalTypes);
        self::assertFalse($type->isNullable);
        self::assertTrue($type->isGeneric);
        self::assertTrue($type->isCollection);

        self::assertSame(['string'], $type->keyType->internalTypes);
        self::assertFalse($type->keyType->isNullable);
        self::assertFalse($type->keyType->isGeneric);
        self::assertFalse($type->keyType->isCollection);

        self::assertSame(['Foo', 'Bar'], $type->valueType->internalTypes);
        self::assertFalse($type->valueType->isNullable);
        self::assertFalse($type->valueType->isGeneric);
        self::assertFalse($type->valueType->isCollection);
    }

    public function dataTokenizeArrayWithNullableKeyType(): array
    {
        return [
            ['array <null | string,Foo>'],
            ['array <?string,Foo>'],
        ];
    }

    /**
     * @dataProvider dataTokenizeArrayWithNullableKeyType
     */
    public function testTokenizeArrayWithNullableKeyType(string $typeExpression): void
    {
        $type = TypeParser::parse($typeExpression);

        self::assertSame(['array'], $type->internalTypes);
        self::assertFalse($type->isNullable);
        self::assertTrue($type->isGeneric);
        self::assertTrue($type->isCollection);

        self::assertSame(['string'], $type->keyType->internalTypes);
        self::assertTrue($type->keyType->isNullable);
        self::assertFalse($type->keyType->isGeneric);
        self::assertFalse($type->keyType->isCollection);

        self::assertSame(['Foo'], $type->valueType->internalTypes);
        self::assertFalse($type->valueType->isNullable);
        self::assertFalse($type->valueType->isGeneric);
        self::assertFalse($type->valueType->isCollection);
    }

    public function dataTokenizeArrayWithNullableValueType(): array
    {
        return [
            ['array <string, null|Foo>'],
            ['array <string, ?Foo>'],
        ];
    }

    /**
     * @dataProvider dataTokenizeArrayWithNullableValueType
     */
    public function testTokenizeArrayWithNullableValueType(string $typeExpression): void
    {
        $type = TypeParser::parse($typeExpression);

        self::assertSame(['array'], $type->internalTypes);
        self::assertFalse($type->isNullable);
        self::assertTrue($type->isGeneric);
        self::assertTrue($type->isCollection);

        self::assertSame(['string'], $type->keyType->internalTypes);
        self::assertFalse($type->keyType->isNullable);
        self::assertFalse($type->keyType->isGeneric);
        self::assertFalse($type->keyType->isCollection);

        self::assertSame(['Foo'], $type->valueType->internalTypes);
        self::assertTrue($type->valueType->isNullable);
        self::assertFalse($type->valueType->isGeneric);
        self::assertFalse($type->valueType->isCollection);
    }
}
