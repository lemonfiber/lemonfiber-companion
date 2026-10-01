<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use ArrayIterator;

use function expect;
use function it;

use Lemonfiber\Sdk\Events\SseParser;
use Modules\Sdk\Internal\AStreamHeldOpen;
use Modules\Sdk\Internal\WhereTheStreamsLeftOff;

/** A stream that carried these bytes and has been read to its end. */
function aStreamThatCarried(string $bytes): AStreamHeldOpen
{
    $held = new AStreamHeldOpen(new ArrayIterator([$bytes]), new SseParser());
    $held->taken();

    return $held;
}

it('opens every stream from the start until one is let go of', function (): void {
    expect(WhereTheStreamsLeftOff::nowhere()->forTheStack('the-loft'))->toBeNull();
});

it('resumes a stack\'s stream after the last event the one before it carried', function (): void {
    $leftOff = WhereTheStreamsLeftOff::nowhere()
        ->keeping('the-loft', aStreamThatCarried("id: 4\ndata: a\n\nid: 7\ndata: b\n\n"));

    expect($leftOff->forTheStack('the-loft'))->toBe('7')
        ->and($leftOff->forTheStack('the-shed'))->toBeNull();
});

it('keeps where a stack left off through a stream that carried no identifier, or none at all', function (): void {
    $leftOff = WhereTheStreamsLeftOff::nowhere()
        ->keeping('the-loft', aStreamThatCarried("id: 7\ndata: b\n\n"))
        ->keeping('the-loft', aStreamThatCarried("data: c\n\n"))
        ->keeping('the-loft', null);

    expect($leftOff->forTheStack('the-loft'))->toBe('7');
});

it('keeps each stack apart', function (): void {
    $leftOff = WhereTheStreamsLeftOff::nowhere()
        ->keeping('the-loft', aStreamThatCarried("id: 7\ndata: b\n\n"))
        ->keeping('the-shed', aStreamThatCarried("id: 2\ndata: b\n\n"));

    expect([$leftOff->forTheStack('the-loft'), $leftOff->forTheStack('the-shed')])->toBe(['7', '2']);
});
