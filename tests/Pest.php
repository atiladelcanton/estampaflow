<?php

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

expect()->extend('toBeUlid', function () {
    return $this->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
});
