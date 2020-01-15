# ocramius/generated-hydrator Symfony 4.4, 5.x bundle

Integrates [ocramius/generated-hydrator](https://github.com/Ocramius/GeneratedHydrator)
library with Symfony.

# Installation

First install the dependency:

```sh
composer require makinacorpus/generated-hydrator-bundle
```

Then add into your `config/bundles.php` file:

```php
<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],

    // ...

    \GeneratedHydrator\Bridge\Symfony\GeneratedHydratorBundle::class => ['all' => true],
];
```

# Configuration

None. Later you will be able to highly customize the hydration process.

# Usage

Inject the `generated_hydrator` service, or type hint with
`GeneratedHydrator\Bridge\Symfony\Hydrator` for auto-wiring.

In order to hydrate an object:

```php

use App\Domain\Model\SomeEntity;
use GeneratedHydrator\Bridge\Symfony\Hydrator;

function some_function(Hydrator $hydrator)
{
    $object = $hydrator->createAndHydrate(
        SomeEntity::class,
        [
            // Scalar values
            'foo' => 1,
            // ...

            // It also handles nested objects
            'bar' => [
                'baz' => 2,
                // ...
            ],
        ]
    );
}
```

Or extract its values:

```php
use App\Domain\Model\SomeEntity;
use GeneratedHydrator\Bridge\Symfony\Hydrator;

function some_function(Hydrator $hydrator)
{
    $object = new SomeEntity();

    $valueArray = $hydrator->extract($object);
}
```

# Some notes

 - we added a recursive nested object hydrator, it uses PHP >= 7.4 type declaration
   to lookup which classes to instantiate and hydrate for nested properties,

 - if you cannot use PHP >= 7.4, consider adding `symfony/property-info` dependency
   for nested property type lookup (it is slow, but it works).
