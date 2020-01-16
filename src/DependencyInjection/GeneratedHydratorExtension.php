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

namespace GeneratedHydrator\Bridge\Symfony\DependencyInjection;

use GeneratedHydrator\Bridge\Symfony\Utils\Psr4Factory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class GeneratedHydratorExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');

        $this->createDefaultPsr4Factory($container, $config);
        $this->configureDefaultHydrator($container, $config);
    }

    /**
     * Create PSR-4 factory object.
     */
    private function createDefaultPsr4Factory(ContainerBuilder $container, array $config): void
    {
        $serviceId = 'generated_hydrator.psr4_configuration';

        $definition = new Definition();
        $definition->setClass(Psr4Factory::class);
        $definition->setPrivate(true);
        $definition->setArguments([
            $config['psr4_source_directory'],
            $config['psr4_namespace_prefix'],
            $config['psr4_namespace_infix'],
        ]);
        $container->setDefinition($serviceId, $definition);

        $container->getDefinition('generated_hydrator.default')->addMethodCall('setPsr4Factory', [new Reference($serviceId)]);
    }

    /**
     * Configures the default hydrator
     */
    private function configureDefaultHydrator(ContainerBuilder $container, array $config): void
    {
        $container
            ->getDefinition('generated_hydrator.default')
            ->setArgument(1, [])
            ->setArgument(2, $config['mode'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new GeneratedHydratorConfiguration();
    }
}
