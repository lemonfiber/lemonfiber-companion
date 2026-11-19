<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowFarSetupGot;
use Modules\Kernel\Api\WhereTheServiceUserComesFrom;
use Tests\Support\TheGeneratedEnvelopes;
use Tests\Support\TheWireUnions;

it('every way setup can stand and every source of the service user the contract describes has a case', function (): void {
    $settled = TheGeneratedEnvelopes::theGeneratedEnvelope('SettledEnvelope');
    $setup = TheWireUnions::unionIn($settled, 'setup');
    $from = TheWireUnions::unionIn($settled, 'from');

    expect($setup)->not->toBe([], 'no setup union was found in the generated envelope')
        ->and($from)->not->toBe([], 'no from union was found in the generated envelope')
        ->and(TheWireUnions::valuesOf(HowFarSetupGot::cases()))->toBe($setup)
        ->and(TheWireUnions::valuesOf(WhereTheServiceUserComesFrom::cases()))->toBe($from);
});
