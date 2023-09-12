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

namespace GeneratedHydrator\Bridge\Symfony\ValueHydrator;

use GeneratedHydrator\Bridge\Symfony\Error\ValueHydratorDoesNotExistError;

class ValueHydratorRegistry
{
    private array $typeCache = [];

    public function __construct(
        private array $instances = [],
    ) {}

    public function hasValueHydrator(string $toType): bool
    {
        try {
            $this->getValueHydrator($toType);
            return true;
        } catch (ValueHydratorDoesNotExistError) {
            return false;
        }
    }

    public function getValueHydrator(string $toType): ValueHydrator
    {
        $index = ($this->typeCache[$toType] ?? null);

        if (null !== $index) {
            return $this->instances[$index];
        }

        $found = null;
        foreach ($this->instances as $index => $instance) {
            if ($instance->supports($toType)) {
                $this->typeCache[$toType] = $index;
                $found = $instance;
                break;
            }
        }

        return $found ?? throw new ValueHydratorDoesNotExistError(\sprintf("No hydrator for type '%s' was found.", $toType));
    }
}
