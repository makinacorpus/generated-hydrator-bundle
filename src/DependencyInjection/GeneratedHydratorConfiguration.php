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
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class GeneratedHydratorConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('generated_hydrator');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->enumNode('mode')
                    ->values(['psr-4', 'cache'])
                    ->defaultValue('psr4')
                ->end()
                ->scalarNode('psr4_namespace_prefix')
                    ->defaultValue('App')
                ->end()
                ->scalarNode('psr4_namespace_infix')
                    ->defaultValue(Psr4Factory::NAMESPACE_INFIX_DEFAULT)
                ->end()
                ->scalarNode('psr4_source_directory')
                    ->defaultValue("%kernel.project_dir%/src")
                ->end()
                ->arrayNode('class_blacklist')
                    ->scalarPrototype()->end()
                ->end()
                /*
                ->arrayNode('runner')
                    ->normalizeKeys(true)
                    ->prototype('array')
                        ->children()
                            ->booleanNode('autocommit')
                                ->defaultFalse()
                            ->end()
                            ->scalarNode('doctrine_connection')
                                ->defaultNull()
                            ->end()
                            ->enumNode('driver')
                                ->values(['doctrine'])
                                ->defaultNull()
                            ->end()
                            ->enumNode('metadata_cache')
                                ->values(['array', 'apcu'])
                                ->defaultNull()
                            ->end()
                            ->scalarNode('metadata_cache_prefix')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('query')
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('domain')
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->booleanNode('event_store')->defaultFalse()->end()
                        ->booleanNode('lock_service')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('preferences')
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        // 'all' means that the whole configuration will be cached
                        // in a single object, none means there will be no cache.
                        ->enumNode('caching_strategy')
                            ->values(['all', 'none'])
                            ->defaultNull()
                        ->end()
                        // Schema definition from configuration
                        ->arrayNode('schema')
                            ->normalizeKeys(true)
                            ->prototype('array')
                                ->children()
                                    // If null, then string
                                    ->enumNode('type')
                                        ->values(['string', 'bool', 'int', 'float'])
                                        ->defaultNull()
                                    ->end()
                                    ->booleanNode('collection')->defaultFalse()->end()
                                    // Default can be pretty much anything, even if type
                                    // is different from what was exposed.
                                    ->variableNode('default')->defaultNull()->end()
                                    // Allowed values should probably be an array of values
                                    // of the same type as upper, but you can put pretty
                                    // much anything in it, validator will YOLO and accept
                                    // anything that's in there.
                                    ->variableNode('allowed_values')->defaultNull()->end()
                                    ->scalarNode('label')->defaultNull()->end()
                                    ->scalarNode('description')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('normalization')
                    ->children()
                        ->variableNode('map')->end()
                        ->variableNode('aliases')->end()
                    ->end()
                ->end()
                 */
            ->end()
        ;

        return $treeBuilder;
    }
}
