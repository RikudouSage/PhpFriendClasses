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

/**
 * @FriendClass(MyOtherClass)
 */
class ClassWithPrivateProperty
{
    private $myPrivateProperty = 0;
}

class MyOtherClass
{
    public function someMethod()
    {
        $privateObject = new ClassWithPrivateProperty();
        $result = $privateObject->myPrivateProperty;
    }
}
```

Notice the `@FriendClass` annotation in docblock of `ClassWithPrivateProperty`.

You can use the annotation multiple times to define multiple friend classes:

```php
<?php

/**
 * @FriendClass(FriendClass1)
 * @FriendClass(FriendClass2)
 */
class MyClass
{
}
```

You must use the fully qualified class name, imports using the `use` keyword are not taken into account.
You can use the name with or without leading backslash.

This will work:

```php
<?php

/**
 * @FriendClass(My\Namespaced\Class1)
 * @FriendClass(\My\Namespaced\Class2)
 */
class MyClass
{
}
```

This will not:

```php
<?php

use My\Namespaced\Class1;

/**
 * The imports are ignored inside the annotation, this won't work
 * @FriendClass(Class1)
 */
class MyClass
{
}
```

## Requirements

The class cannot have magic `__get()` method.

All classes must be loaded using the composer autoloader.

The classes with the annotation must use some standard indentation for it to work, for example this class won't work:

```php
<?php

/**
 * @FriendClass(SomeFriendClass)
 */
class MyClass{private $property = 1;}
```

If you ask why, it's because I'm too lazy to handle such cases.

## Is it slow? Should I use it in production?

Kind of. Should be fast enough when running in production mode but of course it's slower than without using it.

As for whether you should use it, it depends entirely on you. Friend classes are a powerful feature that's easy
to misuse. Also this implementation is far from perfect and is more for a demonstration purposes.

But if you want to use it, you can.

## How does it work?

This library hooks into composer and replaces the autoloader.

Whenever you load a class using the autoloader, it gets checked whether it contains a `@FriendClass` annotation and if
it does, the class is injected with `\Rikudou\FriendClasses\FriendsTrait` trait which does all the work.

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

When you're in production mode the classes are injected during the autoloader dump. When preloading is enabled
no class injecting is done in runtime which speeds up the process significantly.

The downside is that the preloader cannot inject classes that were already loaded during the dump process which should
not be a problem in most cases but can break your app in a few edge cases.

If you want to turn off the preloading, you can do so by setting a config in composer.json like this:

```json5
{
  "require": {
    // your requires
  },
  "extra": {
    "friendClasses": {
      "preload": false
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
    
