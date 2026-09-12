<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Modules\Kernel\Api\Problem;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Sdk\Api\ProblemIsUnreadable;
use Modules\Sdk\Api\Problems;

/**
 * An `error` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, because what is being tested is what
 * happens when the wire says something the contract does not allow — which a
 * client that honoured the contract could never produce.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function errorSaying(array $data): Envelope
{
    return new Envelope(1, 'error', $data);
}

/** @return array<mixed> */
function aWholeError(): array
{
    return [
        'code' => 'STACK-7',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'The media drive is full',
        'meaning' => 'New downloads will fail until space is freed',
        'remedies' => [
            ['action' => 'Free 20 GB on the media drive'],
            ['action' => 'Move finished downloads to the archive'],
        ],
    ];
}

it('reads a whole error into the shape every screen works in', function (): void {
    $problem = Problems::in(errorSaying(aWholeError()));

    expect($problem->code()->shown())->toBe('STACK-7');
    expect($problem->severity())->toBe(Severity::Error);
    expect($problem->standing())->toBe(Standing::Guided);
    expect($problem->summary())->toBe('The media drive is full');
    expect($problem->meaning())->toBe('New downloads will fail until space is freed');
});

it('keeps the remedies in the order the server judged them', function (): void {
    // The order is the server's answer to "what is most likely to work", and it
    // is the one thing a screen cannot work out for itself.
    $remedies = array_map(
        static fn(Remedy $remedy): string => $remedy->action(),
        iterator_to_array(Problems::in(errorSaying(aWholeError()))->remedies(), preserve_keys: false),
    );

    expect($remedies)->toBe([
        'Free 20 GB on the media drive',
        'Move finished downloads to the archive',
    ]);
});

it('reads an error with no remedies as having none, not as broken', function (): void {
    // `Standing::Unknown` is what says a problem has no known remedy, and an
    // empty collection says it without a null anywhere.
    $data = aWholeError();
    unset($data['remedies']);

    expect(Problems::in(errorSaying($data))->remedies()->count())->toBe(0);
});

it('drops detail and cause, which is where that decision is carried out', function (): void {
    // Both are on the wire and neither is on `Problem`, and a remedy's own
    // `detail` is dropped the same way. Nothing would fail if a translation
    // quietly kept them — it would just make the kernel's docblock wrong — so
    // the omission is asserted where it happens.
    $data = aWholeError();
    $data['detail'] = 'ENOSPC writing /data/media';
    $data['cause'] = aWholeError();
    $data['remedies'] = [['action' => 'Free 20 GB', 'detail' => '/data/media, ext4']];

    $problem = Problems::in(errorSaying($data));

    expect($problem->summary())->toBe('The media drive is full');
    expect(array_map(
        static fn(Remedy $remedy): string => $remedy->action(),
        iterator_to_array($problem->remedies(), preserve_keys: false),
    ))->toBe(['Free 20 GB']);
});

it('refuses a severity this app does not read, naming both sides', function (): void {
    $data = aWholeError();
    $data['severity'] = 'catastrophic';

    expect(fn(): Problem => Problems::in(errorSaying($data)))
        ->toThrow(ProblemIsUnreadable::class, 'catastrophic');
});

it('names the words it does read, from the enum rather than a sentence', function (): void {
    // So that a case added to the contract cannot leave the message describing
    // the old vocabulary.
    $data = aWholeError();
    $data['state'] = 'pending';

    expect(fn(): Problem => Problems::in(errorSaying($data)))
        ->toThrow(ProblemIsUnreadable::class, '`suppressed`');
});

it('refuses a missing field rather than filling it in', function (): void {
    // An empty summary renders as a heading with no sentence under it, which
    // reads to an operator as this app having broken.
    $data = aWholeError();
    unset($data['summary']);

    expect(fn(): Problem => Problems::in(errorSaying($data)))
        ->toThrow(ProblemIsUnreadable::class, 'summary');
});

it('refuses a field that is there and is not text', function (): void {
    $data = aWholeError();
    $data['code'] = 7;

    expect(fn(): Problem => Problems::in(errorSaying($data)))
        ->toThrow(ProblemIsUnreadable::class, 'code');
});

it('refuses a remedy that is not an action, naming which one', function (): void {
    $data = aWholeError();
    $data['remedies'] = [['action' => 'Free 20 GB on the media drive'], 'just a string'];

    expect(fn(): Problem => Problems::in(errorSaying($data)))
        ->toThrow(ProblemIsUnreadable::class, 'Remedy 1');
});

it('refuses remedies that are not a list at all', function (): void {
    $data = aWholeError();
    $data['remedies'] = 'none';

    expect(fn(): Problem => Problems::in(errorSaying($data)))
        ->toThrow(ProblemIsUnreadable::class, 'Remedy 0');
});

it('refuses a payload that is not an object at all', function (): void {
    expect(fn(): Problem => Problems::in(new Envelope(1, 'error', 'sorry')))
        ->toThrow(ProblemIsUnreadable::class, 'data');
});

it('leaves the wrong kind to the client to report', function (): void {
    // Holding the kind on the wire against the kind being read for is the
    // client's own business, and it says so better than a second check here
    // would.
    expect(fn(): Problem => Problems::in(new Envelope(1, 'status', aWholeError())))
        ->toThrow(UnexpectedKind::class);
});
