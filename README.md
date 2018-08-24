# Coroutine - component of carno-php

# Installation

```bash
composer require carno-php/coroutine
```

# Concepts

> [Cooperative multitasking using coroutines (in PHP!)](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html)

### yield values

#### Generator

When yield a function and it's returned a generator (yield in function),
job will make a roll and have a chance to execute other jobs,
some functions like ```defer``` depends on this feature

#### Promised

Yield a ```Promise``` will make coroutine sleep when pending, and wakeup when promise resolves or rejects,
general used with async IO

#### Syscall

```Syscall``` can make user in coroutine to get job's properties or interact with job,
such as get job's id or ctx

# Functions

> namespace in ```Carno\Coroutine```

#### go

New coroutine without results but throws exception if error

```php
function go(mixed $program, Context $ctx = null) : void
```

**usages**

```php
// use closure
go(function () {
    yield 'something';
    return 'done';
});

// with exceptions
try {
    go(function () {
        throw new Exception('test');
    });
} catch (Throwable $e) {
    echo 'got exception :: ', $e->getMessage(), PHP_EOL;
}
```

#### co

Similar with ```go``` but returns ```closure``` and you can execute it later

```php
function co(mixed $program, Context $ctx = null) : Closure
```

**usages**

```php
$func = co(function (string $input) {
    yield 'something';
    echo $input;
});
$func('vars');
```

#### async

Base of commands ```go``` and ```co```, it returns a promise that sync with coroutine's result

> Returned promise will resolving when coroutine finish or rejecting when coroutine throws exception

```php
function async(mixed $program, Context $ctx = null, mixed ...$args) : Promised
```

**usages**

```php
async(function () {
    yield msleep(500);
})->then(function () {
    echo 'you will see me after 500ms', PHP_EOL;
});
```

#### await

A delegate for works with async IO event, supports timeout

```php
function await(Closure $dial, Closure $awake,
    int $timeout = 60000, string $error = TimeoutException::class, string $message = '') : Promised
```

**usages**

```php
$async = function ($callback) {
    $callback(111, 222);
};
yield await(function (Closure $awake) use ($async) {
    $async($awake);
}, function (int $a, int $b) {
    echo 'a = ', $a, ' b = ', $b, PHP_EOL;
});
```

#### timeout

Create a promise that throws exception after N milliseconds if no one rejects it in time, useful with ```race```

```php
function timeout(int $ms, string $ec = TimeoutException::class, string $em = '') : Promised
```

**usages**

```php
yield race(timeout(rand(5, 10)), timeout(rand(5, 10), CustomException::class, 'custom message'));
```

#### msleep

Create a promise that resolves after N milliseconds

```php
function msleep(int $ms, Closure $do = null) : Promised
```

**usages**

```php
// make sleep
yield msleep(200);
// do something when wake
echo 'you will see hello -> ', yield msleep(300, function () {
    return 'hello';
}), PHP_EOL;
```

#### ctx

Get job's ctx in coroutine

**usages**

```php
go(function () {
    echo 'hello ', (yield ctx())->get('hello'), PHP_EOL;
}, (new Context)->set('hello', 'world'));
```

#### defer

> A defer statement defers the execution of a function until the surrounding function returns.

**usages**

```php
yield (function () {
    yield defer(function ($stage) {
        // $stage is returned value or last yield value or throwable if exception
        echo 'in defer', PHP_EOL;
    });
    echo 'in end', PHP_EOL;
})();
```

#### race

Same effect as promise's ```race``` but accepts ```Promised``` ```Generator``` and ```Context```

**usages**

```php
yield race((function () {
    echo 'hello ', (yield ctx())->get('hello'), PHP_EOL;
})(), timeout(500), (new Context)->set('hello', 'world'));
```

#### all

Same syntax with ```race```
