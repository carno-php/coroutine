<?php

namespace BM_TEST;

require 'vendor/autoload.php';

use Carno\Coroutine\Job;

$begin = microtime(true);

for ($r = 0; $r < 102400; $r ++) {
    new Job((function () {
        yield 111;
        yield (function () {
            yield 'hello';
            return 'world';
        })();
        yield 222;
        return 333;
    })());
}

$cost = round((microtime(true) - $begin) * 1000);

echo 'cost ', $cost, ' ms | op ', round($cost * 1000 / $r, 3), ' μs', PHP_EOL;
