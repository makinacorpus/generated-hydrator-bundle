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
generated_hydrator:
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


# Roadmap

Reach alpha release (mandatory):

 - [x] implement PSR-4 class name and file name generator,
 - [x] switch to PSR-4 per default,
 - [x] implement class blacklist, some classes such as `\DateTime` and `\Ramsey\Uuid\`
   should be dealt as terminal types, and normalized in the business layer,
 - [ ] autoload classes when they are just generated,
 - [ ] register automatically fallback autoloader for generated hydrator classes,
   without this, classes generated within a cache directory will no be autoloadable.

Industrialisation (1.0):

 - [ ] allow usage of hydrator without the nested implementation explicitely by the
   API user, maybe using a specific interface and a specific service identifier,
 - [ ] nested hydrator is a hack, it should not be the default,
 - [ ] write advanced configuration for users,
 - [ ] write more tests, lots of test.

Far far away:

 - [ ] handle collections in nested extraction/hydration,
 - [ ] add an option to disable property-info usage even when classes are loaded,
 - [ ] remove PHP docblock parser in flavor of our custom one, for this, we need
   to be able to resolve relative class namespaces.
