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
use GeneratedHydrator\Bridge\Symfony\DependencyInjection\GeneratedHydratorExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class KernelConfigurationTest extends TestCase
{
    private function getContainer()
    {
        // Code inspired by the SncRedisBundle, all credits to its authors.
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug'=> false,
            'kernel.bundles' => [],
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => \dirname(__DIR__),
        ]));
    }

    private function getMinimalConfig(): array
    {
        return [];
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testTaggedServicesConfigLoad()
    {
        $extension = new GeneratedHydratorExtension();
        $config = $this->getMinimalConfig();
        $extension->load([$config], $container = $this->getContainer());

        foreach ([
            'generated_hydrator.hydration_plan_builder.reflection',
            'generated_hydrator.deep',
            'generated_hydrator.default',
        ] as $serviceId) {
            self::assertTrue($container->hasDefinition($serviceId));
        }

        self::assertSame(DeepHydrator::class, $container->getDefinition('generated_hydrator.deep')->getClass());
    }
}
