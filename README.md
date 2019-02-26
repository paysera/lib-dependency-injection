# Helper classes for Symfony Dependency Injection component

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

## What is this?

Extra features for easier integration with Symfony Dependency Injection component.

Contains compiler pass for registering tagged services with some another service - no need to write
custom class in each and every case.

## Installation

```bash
composer require paysera/lib-dependency-injection
```

### Basic functionality

To register tagged services to some other service. Optionally passes attributes of the tag, too. 

```php
class SomeBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddTaggedCompilerPass(
            'some_bundle.registry', // ID of service to modify
            'my_provider',          // name of tag to search for
            'addProvider',          // method to call on modified service
            [           // this parameter is optional and defines attributes to pass from tag
                'key',
                'theme' => 'default',   // attribute with default value
                'optional',
            ]
        ));
    }
}
```

```php
class Registry
{
    // first - tagged service. Others (optional) in the order as they come in the attributes array
    public function addProvider(ProviderInterface $provider, $key, $theme, $optional = null)
    {
        $this->providers[$key] = $provider;    // or whatever
    }
}
```

```xml
<service id="some_bundle.registry" class="Acme\Registry">
    <argument>Any arguments service might have</argument>
    
    <!-- Such comment is optional here, but highly recommended for easier debugging: -->
    <!-- Calls addProvider with each service tagged with my_provider -->
</service>

<service id="awesome_provider" class="Acme\AwesomeProvider">
    <tag name="my_provider" key="awesome"/>
</service>
<service id="nice_provider" class="Acme\NiceProvider">
    <tag name="my_provider" key="nice" theme="not a default one"/>
</service>
<service id="superb_provider" class="Acme\SuperbProvider">
    <tag name="my_provider" key="superb" optional="leave theme as default, overwrite optional"/>
    <!-- Same service can have several tags, method will be called for each one of them -->
    <tag name="my_provider" key="superb_dark" theme="dark"/>
</service>
```

### Using priority

Sometimes we need to call method with tagged services by some pre-defined priority.
We could prioritize in the service itself, but this makes code duplicated and also is not
as quick as ordering in compile-time – no need to sort anything in the run-time.

Priority should be enabled when registering compiler pass.
It's provided in `priority` attribute.
Lower the priority, earlier the call.
If priority is not provided, defaults to `0`.

```php
class SomeBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass((new AddTaggedCompilerPass(
            'some_bundle.registry', // ID of service to modify
            'my_provider',          // name of tag to search for
            'addProvider',          // method to call on modified service
            ['key', 'theme' => 'default', 'optional'],
        ))->enablePriority()); // attribute name can be passed here, defaults to `priority`
    }
}
```

```xml
<service id="awesome_provider" class="Acme/AwesomeProvider">
    <tag name="my_provider" key="awesome"/>
</service>
<service id="nice_provider" class="Acme/NiceProvider">
    <tag name="my_provider" key="nice" priority="-1" theme="dark"/>
    <tag name="my_provider" key="fallback" priority="9001" optional="optional param"/>
</service>
<service id="another_provider" class="Acme/AnotherProvider">
    <tag name="my_provider" key="another"/>
</service>
```

Resolves to:

```php
// priority -1 - smallest:
$registry->addProvider($niceProvider, 'nice', 'dark');
// priority defaults to 0, called in the order as registered:
$registry->addProvider($awesomeProvider, 'awesome', 'default');  
$registry->addProvider($anotherProvider, 'another', 'default');
// priority is over 9000:
$registry->addProvider($awesomeProvider, 'fallback', 'default', 'optional param');
```

### Tuning performance

When adding many services by method calls, all of them need to be created when instantiating
the collector service. This could get troublesome if number of services is high.

This is why you can configure a few options to use instead of just passing the service:
- `lazy_service` – pass the service as normally, but mark all services lazy. This makes it
faster in production in most cases, but can be quite slow in development, as container must
be rebuilt every time you modify any of those services (even the code itself);
- `id` – pass only ID of the service, also make tagged service public.
This requires you to inject `container` into your collector and get services
by ID when needed.

```php
class SomeBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass((new AddTaggedCompilerPass(
            'some_bundle.registry', // ID of service to modify
            'my_provider',          // name of tag to search for
            'addProvider',          // method to call on modified service
            ['key', 'theme' => 'default', 'optional'],
        ))->setCallMode(AddTaggedCompilerPass::CALL_MODE_ID));
    }
}
```

```php
class Registry
{
    private $container;

    public function addProvider(string $providerId, $key, $theme, $optional = null)
    {
        $this->providers[$key] = $providerId;
    }
    
    private function getProvider(string $key)
    {
        return $this->container->get($this->providers[$key]);
    }
    
    // ...
}
```

## Semantic versioning

This library follows [semantic versioning](http://semver.org/spec/v2.0.0.html).

See [Symfony BC rules](http://symfony.com/doc/current/contributing/code/bc.html) for basic
information about what can be changed and what not in the API.

## Running tests

```
composer update
composer test
```

## Contributing

Feel free to create issues and give pull requests.

You can fix any code style issues using this command:
```
composer fix-cs
```

[ico-version]: https://img.shields.io/packagist/v/paysera/lib-dependency-injection.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/paysera/lib-dependency-injection/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/paysera/lib-dependency-injection.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/paysera/lib-dependency-injection.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/paysera/lib-dependency-injection.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/paysera/lib-dependency-injection
[link-travis]: https://travis-ci.org/paysera/lib-dependency-injection
[link-scrutinizer]: https://scrutinizer-ci.com/g/paysera/lib-dependency-injection/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/paysera/lib-dependency-injection
[link-downloads]: https://packagist.org/packages/paysera/lib-dependency-injection
