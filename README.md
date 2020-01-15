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

# Autoload

Without this step, this whole bundle is useless. For now, all code that
hydrates or extracts code is stored into Symfony cache folder, add to
your `composer.json`:

```json
    "autoload": {
        "classmap": [
            "var/cache/dev/generated-hydrator",
            "var/cache/prod/generated-hydrator",
            "var/cache/test/generated-hydrator"
        ],
        // ...
    },
```

Then run:

```sh
composer dump-autoload
```

This is inconvenient because you will need to run it in production, after
hydrators have been generated.

A better solution will come later.

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

# Todo list

 - [ ] implement PSR-4 class name and file name generator,
 - [ ] switch to PSR-4 per default,
 - [ ] register automatically fallback autoloader for generated hydrator classes,
 - [ ] implement property blacklist for classes,
 - [ ] implement class blacklist,
 - [ ] write advanced configuration for users,
 - [ ] write more tests.

