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

use GeneratedHydrator\Bridge\Symfony\Error\PropertyDoesNotExistError;

class HydrationPlan
{
    private string $className;
    /** @var HydratedProperty[] */
    private array $properties = [];
    /** @var array<string,string> */
    private array $index = [];

    public function __construct(string $className, iterable $properties)
    {
        $this->className = $className;

        foreach ($properties as $key => $property) {
            if (!$property instanceof HydratedProperty) {
                throw new \InvalidArgumentException(\sprintf("value '%s' is not an instance of '%s'", $key, HydratedProperty::class));
            }
            $this->properties[$property->name] = $property;
            $this->index[$property->alias] = $property->name;
        }
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function isEmpty(): bool
    {
        return empty($this->properties);
    }

    public function getProperty(string $name): HydratedProperty
    {
        return $this->properties[$name] ?? throw new PropertyDoesNotExistError(\sprintf("Property '%s::$%s' does not exist.", $this->className, $name));
    }

    public function getPropertyWithAlias(string $alias): HydratedProperty
    {
        return $this->properties[$this->index[$alias]] ?? throw new PropertyDoesNotExistError(\sprintf("Property of '%s' with alias '%s' does not exist.", $this->className, $alias));
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}
