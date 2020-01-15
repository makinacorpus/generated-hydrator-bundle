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

use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches the hydration plan
 */
final class CachedHydrationPlanBuilder implements HydrationPlanBuilder
{
    const KEY_PREFIX = 'genhydplan-';

    /** @var HydrationPlanBuilder */
    private $decorated;

    /** @var CacheItemPoolInterface */
    private $pool;

    /**
     * Default constructor
     */
    public function __construct(HydrationPlanBuilder $decorated, CacheItemPoolInterface $pool)
    {
        $this->decorated = $decorated;
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $className, array $propertyBlackList = []): HydrationPlan
    {
        $item = $this->pool->getItem(self::KEY_PREFIX.\str_replace('\\', '__', $className));

        if (!$item->isHit() || !($ret = $item->get()) instanceof HydrationPlan || $ret->getClassName() !== $className) {
            $ret = $this->decorated->build($className);
            $this->pool->save($item->set($ret));
        }

        return $ret;
    }
}
