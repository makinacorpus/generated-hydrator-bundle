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

namespace GeneratedHydrator\Bridge\Symfony\Utils\Parser;

/**
 * This is a very primitive, stream based and low memory consuming,
 * non-recursive, stack-based, complex type declaration parser for
 * PHP doc blocks. I guess it is slow. Did not tested it throught.
 *
 * It is NOT accurate, it will let some syntax error pass, but it is
 * already much more than previous naive regex based was doing.
 */
final class TypeParser
{
    /**
     * Tokenize type.
     */
    public static function tokenize(string $value): iterable
    {
        $len = \strlen($value);
        $current = null;
        $currentPos = 0;

        for ($i = 0; $i < $len; ++$i) {
            $char = $value[$i];
            if (' ' === $char) {
                // Ignore blank space. We will parse invalid stuff.
            } else if ('<' === $char || '>' === $char || '|' === $char || ',' === $char) {
                if ($current) {
                    yield [$current, $currentPos];
                    $current = null;
                }
                yield [$char, $i];
            } else {
                if (isset($current)) {
                    $current .= $char;
                } else {
                    $current = $char;
                    $currentPos = $i;
                }
            }
        }
        if ($current) {
            yield [$current, $currentPos];
        }
    }

    private static function last(array $stack): ?ParsedType
    {
        return $stack ? $stack[\count($stack) - 1] : null;
    }

    private static function containsCollection(array $internalTypes): bool
    {
        return (bool)\array_intersect(['array', 'list', 'iterable', '\\Traversable'], $internalTypes);
    }

    public static function parse(string $typeExpression): ?ParsedType
    {
        $ret = null;

        /** @var \GeneratedHydrator\Bridge\Symfony\Utils\Parser\ParsedType $current */
        $current = null;
        $currentIsKey = false;
        /** @var \GeneratedHydrator\Bridge\Symfony\Utils\Parser\ParsedType $current */
        $parent = null;
        $stack = [];

        foreach (self::tokenize($typeExpression) as $token) {
            list($value, $startsAt) = $token;

            if ('<' === $value) {
                if (!$current) {
                    throw new \Exception("Type declaration cannot start with '<' at position #".$startsAt);
                }
                $currentIsKey = true;

                // Change current properties.
                $current->isGeneric = true;

                // Create new current, using actual current as parent.
                $parent = $current;
                \array_push($stack, $parent);
                $current = new ParsedType();

            } else if (',' === $value) {
                if (!$current) {
                    throw new \Exception("Type declaration cannot start with ',' at position #".$startsAt);
                }
                if (!$currentIsKey || !$parent || !$parent->isGeneric) {
                    throw new \Exception("Misplaced ',' at position #".$startsAt);
                }
                $currentIsKey = false;

                // Current becomes its parent key.
                $parent->keyType = $current;

                // Create the new current.
                $current = new ParsedType();

            } else if ('>' === $value) {
                if (!$current) {
                    throw new \Exception("Type declaration cannot start with '>' at position #".$startsAt);
                }
                if (!$parent) {
                    throw new \Exception("Non opened type closing '>' at position #".$startsAt);
                }

                // Check if parent was a collection or not.
                if (self::containsCollection($parent->internalTypes)) {
                    $parent->isCollection = true;
                }

                // Current is now the value type of its parent.
                $parent->valueType = $current;

                // Move back cursor to parent and continue.
                $current = \array_pop($stack);
                $parent = self::last($stack);

            } else if ('|' === $value) {
                if (!$current || (!$current->internalTypes && !$current->isNullable)) {
                    throw new \Exception("Type declaration cannot start with '|' at position #".$startsAt);
                }
                // In all cases, just ignore "|".
            } else {
                // Create new item, if there was not.
                if (!$current) {
                    $current = new ParsedType();
                    if (!$ret) {
                        $ret = $current;
                    }
                }

                if ('null' === $value) {
                    $current->isNullable = true;
                } else {
                    if ('?' === $value[0]) {
                        $value = \substr($value, 1);
                        $current->isNullable = true;
                    }
                    if ('[]' === \substr($value, -2)) {
                        $current->valueType = new ParsedType();
                        $current->valueType->internalTypes[] = \substr($value, 0, -2);
                        $current->isCollection = true;
                        $current->isGeneric = true;
                    } else {
                        $current->internalTypes[] = $value;
                    }
                }
            }
        }

        return $ret;
    }
}
