<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Sdk\Api\HandleIsUnreadable;
use Modules\Sdk\Api\Handles;
use Tests\Support\WhatTheContractAccepts;

/**
 * A `job` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, because what is being tested is what
 * happens when the wire says something the contract does not allow — which a
 * client that honoured the contract could never produce.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function anAcknowledgementSaying(array $data): Envelope
{
    return new Envelope(1, 'job', $data);
}

/**
 * What a stack answers an action with, complete.
 *
 * @return array<string, mixed>
 */
function whatAStackAnswersAnActionWith(): array
{
    return ['action' => 'repair', 'job' => 'a-job-name'];
}

it('reads the handle a stack answered an action with', function (): void {
    $job = Handles::in(anAcknowledgementSaying(whatAStackAnswersAnActionWith()));

    expect($job->shown())->toBe('a-job-name');
});

it('reads the same handle whichever action was acknowledged', function (): void {
    // The `action` field is not read, and this is what says so: a start and a
    // repair are one envelope here, and a reader that checked the word would be
    // this app telling a stack what it had just been asked.
    $job = Handles::in(anAcknowledgementSaying(['action' => 'down', 'job' => 'a-job-name']));

    expect($job->shown())->toBe('a-job-name');
});

it('refuses an acknowledgement with no job name in it', function (): void {
    // The state nothing has an answer for: the action was delivered, so it
    // must not be sent again, and there is nothing to ask after it by.
    expect(fn(): object => Handles::in(anAcknowledgementSaying(['action' => 'repair'])))
        ->toThrow(HandleIsUnreadable::class, 'job');
});

it('refuses an acknowledgement whose job name is not text', function (): void {
    expect(fn(): object => Handles::in(anAcknowledgementSaying(['action' => 'repair', 'job' => 41])))
        ->toThrow(HandleIsUnreadable::class, 'job');
});

it('refuses an acknowledgement whose job name is blank', function (): void {
    // Refused one layer further in, by `Job` itself — a name present and empty
    // is a handle in shape and nothing in substance.
    expect(fn(): object => Handles::in(anAcknowledgementSaying(['action' => 'repair', 'job' => '   '])))
        ->toThrow(JobHasNoName::class);
});

it('refuses an acknowledgement whose payload is not a shape at all', function (): void {
    // The generated envelope asserts its payload's shape without checking it,
    // and that assertion is not a fact about the socket. This is the case where
    // the two differ: a body that parsed as JSON and is not an object.
    expect(fn(): object => Handles::in(new Envelope(1, 'job', 'a sentence where a payload belongs')))
        ->toThrow(HandleIsUnreadable::class, 'data');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    // Held to the generated types rather than to the reader, because a fixture
    // is written by whoever wrote the reader: where the two agree about a field
    // that is not there, both are wrong in the same direction and every case
    // above is green against a machine nobody has run them against.
    expect(WhatTheContractAccepts::complaintsAbout('JobEnvelope', [
        'kind' => 'job',
        'data' => whatAStackAnswersAnActionWith(),
    ]))->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
