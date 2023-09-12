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

namespace GeneratedHydrator\Bridge\Symfony\HydrationPlan;

/**
 * @internal
 *
 * Represent a typed object property, such as:
 *   - int $foo
 *   - \Some\Class $foo
 *   - ?\Some\Class $foo
 *   - int|string|\Some\Class $foo
 *
 * It can represent a value collection, such as:
 *   - int[] $foo
 *   - \Some\Class[] $foo
 *   - array<int|string|\Some\Class> $foo
 *
 * But it cannot represent a union type of a collection and a non
 * collection types.
 */
final class HydratedProperty
{
    public function __construct(
        public readonly string $className,
        /** PHP property name. */
        public readonly string $name,
        /** Remote contextual property name. */
        public readonly ?string $alias = null,
        /** PHP native type names, empty means unknown. */
        public readonly array $nativeTypes = [],
        public readonly bool $allowsNull = true,
        public readonly bool $collection = false,
        /** @todo Unused for now. */
        public readonly ?string $collectionType = null,
        /** Was typed determined completely. */
        public readonly bool $complete = true,
    ) {}
}
