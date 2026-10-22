<?php

declare(strict_types=1);

use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhereHearingLeftOff;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformWhereHearingLeftOff;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\WhereHearingLeftOffInMemory;
use Tests\Support\WhereItResumes;

// The WhereHearingLeftOff contract, run against the platform store and the fake.
//
// What both must agree on is what a stream opened later resumes after: the
// last event kept for that stack, until another replaces it or it is let go
// of, and nothing where the device would not keep it.

/** A stack whose stream was heard. Named for this file: the root suites share one namespace (G10). */
function aStackWhoseStreamWasHeard(string $seed): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** @return array<string, array{Closure(): WhereHearingLeftOff}> */
dataset('every place hearing left off', [
    'the platform store' => [fn(): WhereHearingLeftOff => new PlatformWhereHearingLeftOff(APlatformStore::working(), new PlatformStacks(APlatformStore::working()))],
    'the fake' => [fn(): WhereHearingLeftOff => WhereHearingLeftOffInMemory::nowhere()],
]);

it('keeps nothing before a stream was heard', function (WhereHearingLeftOff $leftOff): void {
    expect(WhereItResumes::said($leftOff->leftOffAt(aStackWhoseStreamWasHeard('a'))))->toBe('from-the-start')
        ->and($leftOff->keepsAnythingOf(aStackWhoseStreamWasHeard('a')))->toBeFalse();
})->with('every place hearing left off');

it('keeps the last event each stack was heard to, the newest replacing the one before', function (WhereHearingLeftOff $leftOff): void {
    $kept = $leftOff->heardTo(aStackWhoseStreamWasHeard('a'), AnEventHeard::named('run-1'));
    $leftOff->heardTo(aStackWhoseStreamWasHeard('a'), AnEventHeard::named('run-2'));
    $leftOff->heardTo(aStackWhoseStreamWasHeard('b'), AnEventHeard::named('run-7'));

    expect($kept)->toBeTrue()
        ->and(WhereItResumes::said($leftOff->leftOffAt(aStackWhoseStreamWasHeard('a'))))->toBe('after:run-2')
        ->and(WhereItResumes::said($leftOff->leftOffAt(aStackWhoseStreamWasHeard('b'))))->toBe('after:run-7');
})->with('every place hearing left off');

it('lets go of one stack\'s part and keeps the others', function (WhereHearingLeftOff $leftOff): void {
    $leftOff->heardTo(aStackWhoseStreamWasHeard('a'), AnEventHeard::named('run-1'));
    $leftOff->heardTo(aStackWhoseStreamWasHeard('b'), AnEventHeard::named('run-2'));

    expect($leftOff->forgetTheStack(aStackWhoseStreamWasHeard('a'))->howMany())->toBe(1)
        ->and($leftOff->forgetTheStack(aStackWhoseStreamWasHeard('a'))->howMany())->toBe(0)
        ->and(WhereItResumes::said($leftOff->leftOffAt(aStackWhoseStreamWasHeard('a'))))->toBe('from-the-start')
        ->and($leftOff->keepsAnythingOf(aStackWhoseStreamWasHeard('a')))->toBeFalse()
        ->and(WhereItResumes::said($leftOff->leftOffAt(aStackWhoseStreamWasHeard('b'))))->toBe('after:run-2');
})->with('every place hearing left off');

it('says an event it could not keep was not kept, and resumes from the start', function (WhereHearingLeftOff $refusing): void {
    expect($refusing->heardTo(aStackWhoseStreamWasHeard('a'), AnEventHeard::named('run-1')))->toBeFalse()
        ->and(WhereItResumes::said($refusing->leftOffAt(aStackWhoseStreamWasHeard('a'))))->toBe('from-the-start');
})->with([
    'the platform store' => [fn(): WhereHearingLeftOff => new PlatformWhereHearingLeftOff(APlatformStore::refusing(), new PlatformStacks(APlatformStore::refusing()))],
    'a device with no store' => [fn(): WhereHearingLeftOff => new PlatformWhereHearingLeftOff(APlatformStore::absent(), new PlatformStacks(APlatformStore::absent()))],
    'the fake' => [fn(): WhereHearingLeftOff => WhereHearingLeftOffInMemory::refusing()],
]);
