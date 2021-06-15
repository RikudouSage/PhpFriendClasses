# Friend classes in PHP

Inspired by the concept of friend classes in C++ where you can specify classes that have access
to otherwise unaccessible properties.

Example from C++ world:

```c++
class ClassWithPrivateProperty {
private:
    int myPrivateProperty = 0;

    friend class MyOtherClass; // here we declare MyOtherClass as a friend
}

class MyOtherClass {
public:
    void someMethod() {
        ClassWithPrivateProperty privateObject;
        auto result = privateObject.myPrivateProperty; // allowed because MyOtherClass is declared as a friend of ClassWithPrivateProperty
    }    
}
```

## Installation

`composer require rikudou/friend-classes`

That's it, the package is now automatically enabled and works out of the box.

## Usage

After installing this package you can use similar concept in PHP:

```php
<?php

use Rikudou\FriendClasses\Attribute\FriendClass;

#[FriendClass(MyOtherClass::class)]
class ClassWithPrivateProperty
{
    private int $myPrivateProperty = 0;
    
    private function myPrivateMethod(): bool
    {
        return true;
    }
}

class MyOtherClass
{
    public function someMethod(): void
    {
        $privateObject = new ClassWithPrivateProperty();
        $result = $privateObject->myPrivateProperty;
        $result = $privateObject->myPrivateMethod();
    }
}
```

Notice the `#[FriendClass]` attribute above class `ClassWithPrivateProperty`.

You can use it for properties as well as methods.

You can use the attribute multiple times to define multiple friend classes:

```php
<?php

use Rikudou\FriendClasses\Attribute\FriendClass;

#[FriendClass('FriendClass1')]
#[FriendClass('FriendClass2')]
class MyClass
{
}
```

## Allow only certain methods/properties

If you want to allow access only to certain properties/methods, you can define the `FriendClass` attribute
on the property/method directly. In that case the class needs to have the `#[HasFriendClasses]` attribute or at
least one `#[FriendClass]` attribute.

```php
<?php

use Rikudou\FriendClasses\Attribute\HasFriendClasses;
use Rikudou\FriendClasses\Attribute\FriendClass;

#[HasFriendClasses]
class MyPrivateClass {
    #[FriendClass(ClassWithAccessToPrivateProperties::class)]
    private int $someProperty = 1;
    private int $someOtherProperty = 2;
    
    #[FriendClass(ClassWithAccessToPrivateProperties::class)]
    private function someMethod(): void
    {
        // nothing to do
    }
    
    private function someOtherMethod(): void
    {
        // nothing to do
    }
}

class ClassWithAccessToPrivateProperties
{
    public function __construct()
    {
        $privateClass = new MyPrivateClass();
        var_dump($privateClass->someProperty); // will dump 1
        var_dump($privateClass->someOtherProperty); // will throw an error because this class is not a friend
        $privateClass->someMethod(); // won't fail
        $privateClass->someOtherMethod(); // will throw an error because this class is not a friend
    }
}
```

As mentioned before, the class doesn't need to have the `#[HasFriendClasses]` if it already contains `#[FriendClass]`
attribute. The `#[HasFriendClasses]` is only a hint for the parser to inspect the class which happens also if there's
a `#[FriendClass]` attribute.

In the following example there's no `#[HasFriendClass]` attribute and `Class1` has access to all private
properties/methods of `PrivateClass` while `Class2` only has access to some.

```php
<?php

use Rikudou\FriendClasses\Attribute\FriendClass;

#[FriendClass(Class1::class)]
class PrivateClass
{
    #[FriendClass(Class2::class)]
    private int $accessibleToBothClasses = 1;
    private int $accessibleOnlyToClass1 = 2;
}

class Class1
{
    public function __construct()
    {
        $instance = new PrivateClass();
        $instance->accessibleToBothClasses;
        $instance->accessibleOnlyToClass1;
        var_dump('This will get dumped because Class1 is a friend of the whole class and thus has access to everything');
    }
}

class Class2
{
    public function __construct()
    {
        $instance = new PrivateClass();
        $instance->accessibleToBothClasses;
        $instance->accessibleOnlyToClass1;
        var_dump('This will not get dumped because Class2 is only a friend to the $accessibleToBothClasses field');
    }
}

```

## Configuration

All configuration is done inside the composer.json file in `extra`.`friendClasses` and is optional.

### Mode

You can set whether you want access to properties, methods or both. Default is both.

```json5
{
  "extra": {
    "friendClasses": {
      "mode": "methods" // or "both" or "properties"
    }
  }
}
```

### Preload

Whether to enable class preloading in production mode, see description below. Default is false.

```json5
{
  "extra": {
    "friendClasses": {
      "preload": true
    }
  }
}
```

## Requirements

The class cannot have magic `__get()` and `__call()` methods.

All classes must be loaded using the composer autoloader.

The classes with the annotation must use some standard indentation for it to work, for example this class won't work:

```php
<?php

use Rikudou\FriendClasses\Attribute\FriendClass;

 #[FriendClass('SomeFriendClass')]
class MyClass{private $property = 1;}
```

It's because I'm too lazy to handle such cases.

## Is it slow? Should I use it in production?

Kind of. Should be fast enough when running in production mode but of course it's slower than when not using it.

As for whether you should use it, it depends entirely on you. Friend classes are a powerful feature that's easy
to misuse. Also this implementation is far from perfect and is more for a demonstration purposes.

But if you want to use it, you can.

## How does it work?

This library hooks into composer and replaces the autoloader.

Whenever you load a class using the autoloader, it gets checked whether it contains a `#[FriendClass]` attribute and if
it does, the class is injected with some traits that do all the work.

The trait defines a magic `__get()` method and using `debug_backtrace()` checks who the caller is. If the caller is
one of the friend classes, it returns the value of the property, otherwise throws an `Error`. If the property does not
exist a notice is generated (same as php itself does).

## Production mode and dev mode

If you're in dev mode, the class is injected with the trait every time, meaning it's kinda slow because the autoloader
has to inspect the class content and inject the trait for every run. This ensures that when you make a change to
the original class, the injected class will get updated as well.

In production mode once the injected class is generated, it's not reevaluated until you clear the cache (cache is
cleared when composer generates new autoloader, e.g. during `install`, `update`, `require`, `dump-autoload` etc.).

To enable the production mode, simply use the flag `--optimize` (or `--classmap-authoritative` or the shortcuts
`-o` or `-a`) when generating the composer autoloader.

## Preloading in production mode

When you're in production mode the classes can be injected during the autoloader dump. When preloading is enabled
no class injecting is done in runtime which speeds up the process significantly.

The downside is that the preloader cannot inject classes that were already loaded during the dump process which should
not be a problem in most cases but can break your app in a few edge cases.

The other downside is that when the class cannot be loaded (e.g. due to extending non-existing class), it fails,
for example Symfony does this a lot.

If you want to enable the preloading, you can do so by setting a config in composer.json like this:

```json5
{
  "require": {
    // your requires
  },
  "extra": {
    "friendClasses": {
      "preload": true
    }
  }
}
```

Examples:

- Production mode won't be enabled:
    - `composer install`
    - `composer update`
    - `composer require vendor/package`
    - `composer dump-autoload`
- Production mode will be enabled:
    - `composer install --optimize`
    - `composer install --classmap-authoritative`
    - `composer install -o`
    - `composer install -a`
    - `composer update --optimize`
    - `composer update --classmap-authoritative`
    - `composer update -o`
    - `composer update -a`
    - `composer require vendor/package --optimize`
    - `composer require vendor/package --classmap-authoritative`
    - `composer require vendor/package -o`
    - `composer require vendor/package -a`
    - `composer dump-autoload --optimize`
    - `composer dump-autoload --classmap-authoritative`
    - `composer dump-autoload -o`
    - `composer dump-autoload -a`
    
