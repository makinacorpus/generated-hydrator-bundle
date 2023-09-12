# ocramius/generated-hydrator Symfony >=6.x bundle

Integrates [ocramius/generated-hydrator](https://github.com/Ocramius/GeneratedHydrator)
library with Symfony.

It also brings some new features:

 - A nested hydrator, that from PHP properties type will cascade hydration
   into an object graph.

 - Value hydrators, for each PHP type you can plug your own global hydrator
   implementation for dealing with custom types.

 - Next planned feature will be object configuration using attributes.


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

## Change generated PHP file location

Default configuration will attempt to write generated hydrator code into the
`%kernel.project_dir%/hydrator` folder. For this to work, you probably want to
add in your `composer.json` file:

```json
{
    "autoload": {
        "classmap": [
            "hydrator"
        ]
    }
}
```

You can change the target by adding the following
`config/packages/generated-hydrator.yaml` file:

```yaml
generated_hydrator:
    target_directory: "%kernel.project_dir%/hydrator"
```

## Blacklist classes from hydration

You may experience bugs at some point when the hydrator attempts for example
to hydrate PHP core classes. In order to avoid this from happening, you can
completely disable hydration attempts on any PHP type, by using the following
configuration:

```yaml
generated_hydrator:
    class_blacklist:
        - App\SomeClass
        - DateTime
        - DateTimeImmutable
        - DateTimeInterface
        - Ramsey\Uuid\Uuid
        - Ramsey\Uuid\UuidInterface
        # ...
```

This will prevent the nested object hydrator from those classes hydration
attempt.


## Pre-generate class hydrators for production

You can setup a list of class for which you need to pre-generate hydrators:

```yaml
generated_hydrator:
    class_list:
        - App\Entity\Foo
        - App\Entity\Bar
        # ...
```

This will allow the `generated-hydrator:generate` command to pre-generate
all hydrators.


# Autowiring

You can use the `GeneratedHydrator\Bridge\Symfony\HydratorAware` interface
and set it on services, which will make this bundle autoconfigure the
service injection for you.

You can also use the `GeneratedHydrator\Bridge\Symfony\HydratorAwareTrait`
if you don't want to implement the `setObjectHydrator()` method by yourself.


# Usage

## Basic usage

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


## Value hydrator

Let's consider you have the following class:

```php
namespace App\Entity;

interface Identifier
{
    public function __construct(mixed $value);

    public function __toString(): string;
}

class FooId
{
    public function __construct(
        private mixed $value,
    ) {}

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
```

And use it as an identifier class for the following entity class:

```php
namespace App\Entity;

class Foo
{
    public function __construct(
        private FooId $id,
    ) {}

    // ...
}
```

If you need to hydrate some `Foo` instance using the following data from database:

```php
$foo = $hydrator->createAndHydrate(
    \App\Entity\Foo::class,
    [
        'id' => '12345',
    ],
);
```

This will fail because the `$id` parameter cannot accept a `string` value.

If you use the same pattern all over your site, you might want to use a global
value hydrator, as such:

```php
namespace App\ValueHydrator;

use App\Entity\Identifier;
use GeneratedHydrator\Bridge\Symfony\Error\CannotHydrateValueError;
use GeneratedHydrator\Bridge\Symfony\ValueHydrator\ValueHydrator;

class IdentifierValueHydrator implements ValueHydrator
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $phpType): bool
    {
        return \is_subclass_of($phpType, Identifier::class);
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(string $phpType, mixed $value): mixed
    {
        if (\is_subclass_of($phpType, Identifier::class)) {
            return new $phpType($value);
        }
        throw new CannotHydrateValueError();
    }
}
```

And register it either by explicitely setting the `generated_hydrator.value_hydrator`
service tag, or by adding into the container using `autowiring` and `autoconfiguration`
set to `true` on this service:

```yaml
services:
    App\ValueHydrator\IdentifierValueHydrator:
        autowire: true
        autoconfigure: true
```

Once this set, each property value that needs to be hydrated implementing
your `App\Entity\Identifier` interface will have its value correctly
instanciated automatically.


# Some notes

For the nested hydrator to work, it needs to be able to use introspection
on you classes for finding the properties types.

If for some reason introspection fails, you can explicitely install
the `symfony/property-access` component, which may find some types that this
API is unable to find using reflection.


# Roadmap

Reach alpha release (mandatory):

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
