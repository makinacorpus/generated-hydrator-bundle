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

Per default, this bundle will configure the hydrator generator to write
hydrator classes under your `src/` directory, for example, the  `App\\Entity\User`
class will have the following hydrator: `App\\Hydrator\\Entity\UserHydrator`,
written in the `src/Hydrator/Entity/UserHydrator.php` file.

In order to change the naming strategy, copy-paste the
`src/Resources/config/packages/generated-hydrator.yaml` from this package in your
project's `config/packages/` directory, and edit the following lines:

```yaml
generated-hydrator:
    # ...
    psr4_namespace_prefix: App
    psr4_namespace_infix: Hydrator
```

 - `psr4_namespace_prefix` is your application namespace, it could be anything
   such as `YourVendor\YourApplication` as long as matches one of the PSR-4 entries
   in your `composer.json` file's `autoload` section,

 - `psr4_namespace_infix` is the sub-namespace in which the generated classe will
   be, please note that if you set it to `null`, generated hydrator classes will
   be written in the same folder as their corresponding entities.


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

 - [x] implement PSR-4 class name and file name generator,
 - [x] switch to PSR-4 per default,
 - [ ] register automatically fallback autoloader for generated hydrator classes,
 - [ ] implement property blacklist for classes,
 - [ ] implement class blacklist,
 - [ ] write advanced configuration for users,
 - [ ] write more tests.

