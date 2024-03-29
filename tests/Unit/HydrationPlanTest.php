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

use GeneratedHydrator\Bridge\Symfony\HydrationPlan\HydratedProperty;
use GeneratedHydrator\Bridge\Symfony\HydrationPlan\HydrationPlan;
use PHPUnit\Framework\TestCase;

final class HydrationPlanTest extends TestCase
{
    public function testGetClassName(): void
    {
        $hydrationPlan = new HydrationPlan('Foo\\Bar', []);

        self::assertSame('Foo\\Bar', $hydrationPlan->getClassName());
    }

    public function testIsEmpty(): void
    {
        $hydrationPlan = new HydrationPlan('Foo\\Bar', []);

        self::assertTrue($hydrationPlan->isEmpty());

        $property0 = new HydratedProperty(
            name: 'property0',
            className: 'Baz',
        );

        $hydrationPlan = new HydrationPlan('Foo\\Bar', [
            $property0,
        ]);

        self::assertFalse($hydrationPlan->isEmpty());
    }

    public function testInvalidConstructorArgumentsRaiseError(): void
    {
        self::expectExceptionMessageMatches('@' . \str_replace('\\', '\\\\', HydratedProperty::class ) . '@');

        new HydrationPlan('Foo\\Bar', [
            'foo' => new \DateTime(),
        ]);
    }
}
