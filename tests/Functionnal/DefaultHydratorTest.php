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

use GeneratedHydrator\Bridge\Symfony\DeepHydrator;
use GeneratedHydrator\Bridge\Symfony\DefaultHydrator;
use GeneratedHydrator\Bridge\Symfony\Hydrator;
use GeneratedHydrator\Bridge\Symfony\Tests\Mock\ClassWithNestedObject;
use GeneratedHydrator\Bridge\Symfony\Tests\Mock\SimpleClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class DefaultHydratorTest extends TestCase
{
    private function createHydrator(): Hydrator
    {
        $filesystem = new Filesystem();

        $generatedClassesTargetDir = \dirname(__DIR__).'/cache';
        if ($filesystem->exists($generatedClassesTargetDir)) {
            $filesystem->remove($generatedClassesTargetDir);
        }
        $filesystem->mkdir($generatedClassesTargetDir);

        return new DeepHydrator(
            new DefaultHydrator(
                $generatedClassesTargetDir,
                [
                    /*
                    'class' => [
                        'auto_generate_proxies' => true,
                        'class_name' => null,
                        'class_namespace' => null,
                        'target_dir' => $this->generatedClassesTargetDir,
                    ],
                     */
                ]
            )
        );
    }

    public function testSimpleClassHydration(): void
    {
        $hydrator = $this->createHydrator();

        $object = $hydrator->createAndHydrate(SimpleClass::class, [
            'property0' => 12,
            'property1' => 7,
            'property2' => 'bar',
        ]);

        self::assertInstanceOf(SimpleClass::class, $object);
        self::assertSame(12, $object->getProperty0());
        self::assertSame(7, $object->getProperty1());
        self::assertSame('bar', $object->getProperty2());
    }

    public function testSimpleClassHydrationWithPsr4ModePerConfiguration(): void
    {
        self::markTestIncomplete();
    }

    public function testSimpleClassHydrationWithWithCustomConfiguration(): void
    {
        self::markTestIncomplete();
    }

    public function testClassWithPlanAndAlreadyHydratedPropertiesHydration(): void
    {
        $hydrator = $this->createHydrator();

        $nested = new SimpleClass(7, 11, null);
        $object = $hydrator->createAndHydrate(ClassWithNestedObject::class, [
            'property0' => $nested,
            'property1' => [
                'property0' => null,
            ],
        ]);

        self::assertInstanceOf(ClassWithNestedObject::class, $object);

        $object1 = $object->getProperty0();
        self::assertInstanceOf(SimpleClass::class, $object1);
        self::assertSame($nested, $object1);
        self::assertSame(7, $object1->getProperty0());
        self::assertSame(11, $object1->getProperty1());
        self::assertNull($object1->getProperty2());
    }

    public function testClassWithPlanHydration(): void
    {
        $hydrator = $this->createHydrator();

        $object = $hydrator->createAndHydrate(ClassWithNestedObject::class, [
            'property0' => [
                'property0' => 7,
                'property1' => 11,
            ],
            'property1' => [
                'property0' => [
                    'property0' => 13,
                    'property1' => 19,
                ]
            ],
        ]);

        self::assertInstanceOf(ClassWithNestedObject::class, $object);

        $object1 = $object->getProperty0();
        self::assertInstanceOf(SimpleClass::class, $object1);
        self::assertSame(7, $object1->getProperty0());
        self::assertSame(11, $object1->getProperty1());
        self::assertNull($object1->getProperty2());

        $object2 = $object->getProperty1();
        self::assertInstanceOf(ClassWithNestedObject::class, $object2);
        self::assertNull($object2->getProperty1());

        $object3 = $object2->getProperty0();
        self::assertInstanceOf(SimpleClass::class, $object3);
        self::assertSame(13, $object3->getProperty0());
        self::assertSame(19, $object3->getProperty1());
        self::assertNull($object3->getProperty2());
    }

    public function testSimpleClassExtractionWithPsr4ModePerConfiguration(): void
    {
        self::markTestIncomplete();
    }

    public function testSimpleClassExtractionWithWithCustomConfiguration(): void
    {
        self::markTestIncomplete();
    }

    public function testClassWithPlanExtraction(): void
    {
        self::markTestIncomplete();
    }
}
