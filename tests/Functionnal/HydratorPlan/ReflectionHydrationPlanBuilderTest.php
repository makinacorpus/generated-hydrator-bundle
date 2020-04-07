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

namespace GeneratedHydrator\Bridge\Symfony\Tests\Functionnal;

use GeneratedHydrator\Bridge\Symfony\HydrationPlan\ReflectionHydrationPlanBuilder;
use GeneratedHydrator\Bridge\Symfony\Tests\Mock\QueuedMessage;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

final class ReflectionHydrationPlanBuilderTest extends TestCase
{
    /**
     * Real life use case #1.
     */
    public function testQueuedMessage(): void
    {
        $builder = new ReflectionHydrationPlanBuilder();
        $builder->disablePropertyTypeReflection();

        $plan = $builder->build(QueuedMessage::class);
        $properties = $plan->getProperties();

        self::assertCount(3, $properties);

        foreach ($properties as $property) {
            switch ($property->name) {

                case 'id':
                    self::assertSame(UuidInterface::class, $property->className);
                    self::assertFalse($property->collection);
                    self::assertFalse($property->allowsNull);
                    break;

                case 'created_at':
                    self::assertSame(\DateTimeInterface::class, $property->className);
                    self::assertFalse($property->collection);
                    self::assertFalse($property->allowsNull);
                    break;

                case 'consumed_at':
                    self::assertSame(\DateTimeInterface::class, $property->className);
                    self::assertFalse($property->collection);
                    self::assertTrue($property->allowsNull);
                    break;

                default:
                    self::fail("Unexpected property ".$property->name." was found.");
            }
        }
    }
}
