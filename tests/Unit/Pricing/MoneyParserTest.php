<?php

declare(strict_types=1);

use App\Domains\Pricing\ValueObjects\MoneyParser;

it('converte valores monetários sem usar float', function (): void {
    $parser = new MoneyParser;

    expect($parser->majorToMinor('10,50'))->toBe(1050)
        ->and($parser->majorToMinor('10.50'))->toBe(1050)
        ->and($parser->majorToMinor('1.234,56'))->toBe(123456)
        ->and($parser->minorToMajor(123456))->toBe('1234,56');
});
