<?php

declare(strict_types=1);

use Modules\Kernel\Api\AnOriginIsUnnamed;
use Modules\Kernel\Api\WhatItReplaced;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Sdk\Api\OriginIsUnreadable;
use Modules\Sdk\Internal\Attributions;

/** The arm an origin was read on, carried out of a fold that hands back objects. */
final readonly class WhoTheRowNamed
{
    public function __construct(public string $said) {}
}

/**
 * Who a row carrying this `origin` was put there by, as one line.
 *
 * Every arm hands back what it was given, so a reader that fired the right arm
 * with the wrong payload fails here rather than passing on the arm alone.
 */
function whoPutIt(mixed $origin): string
{
    return whoTheOriginNamed(Attributions::of(['origin' => $origin]));
}

/** An origin, as one line: which arm, what it names, and what an override replaced. */
function whoTheOriginNamed(WhoPutItThere $origin): string
{
    return $origin->whichever(
        bundled: static fn(): WhoTheRowNamed => new WhoTheRowNamed('bundled'),
        operator: static fn(): WhoTheRowNamed => new WhoTheRowNamed('operator'),
        plugin: static fn(string $named): WhoTheRowNamed => new WhoTheRowNamed(sprintf('plugin:%s', $named)),
        unknown: static fn(string $why): WhoTheRowNamed => new WhoTheRowNamed(sprintf('unknown:%s', $why)),
        overridden: static fn(string $named, WhatItReplaced $replaced): WhoTheRowNamed => new WhoTheRowNamed(sprintf(
            'overridden:%s over %s from %s',
            $named,
            $replaced->whichever(
                held: static fn(string $value): WhoTheRowNamed => new WhoTheRowNamed(sprintf('"%s"', $value)),
                nothingSet: static fn(): WhoTheRowNamed => new WhoTheRowNamed('nothing'),
                withheld: static fn(): WhoTheRowNamed => new WhoTheRowNamed('a secret'),
            )->said,
            whoTheOriginNamed($replaced->from()),
        )),
        orphaned: static fn(string $named): WhoTheRowNamed => new WhoTheRowNamed(sprintf('orphaned:%s', $named)),
    )->said;
}

it('reads every arm, and hands each what it was given', function (): void {
    expect(whoPutIt(['origin' => 'bundled']))->toBe('bundled')
        ->and(whoPutIt(['origin' => 'operator']))->toBe('operator')
        ->and(whoPutIt(['origin' => 'plugin', 'named' => 'plex']))->toBe('plugin:plex')
        ->and(whoPutIt(['origin' => 'unknown', 'why' => 'the stack was rebuilt']))->toBe('unknown:the stack was rebuilt')
        ->and(whoPutIt(['origin' => 'orphaned', 'named' => 'plex']))->toBe('orphaned:plex');
});

it('reads what an override replaced, and where that came from, however deep', function (): void {
    $over = static fn(array $replaced): array => ['origin' => 'overridden', 'named' => 'plex', 'replaced' => $replaced];

    expect(whoPutIt($over(['withheld' => false, 'value' => '8096', 'from' => ['origin' => 'bundled']])))->toBe('overridden:plex over "8096" from bundled')
        ->and(whoPutIt($over(['withheld' => false, 'value' => null, 'from' => ['origin' => 'bundled']])))->toBe('overridden:plex over nothing from bundled')
        ->and(whoPutIt($over(['withheld' => false, 'from' => ['origin' => 'operator']])))->toBe('overridden:plex over nothing from operator')
        ->and(whoPutIt($over(['withheld' => true, 'value' => 'hunter2', 'from' => ['origin' => 'plugin', 'named' => 'arr']])))->toBe('overridden:plex over a secret from plugin:arr')
        ->and(whoPutIt($over(['withheld' => false, 'value' => '1', 'from' => $over(['withheld' => false, 'value' => '0', 'from' => ['origin' => 'bundled']])])))
        ->toBe('overridden:plex over "1" from overridden:plex over "0" from bundled');
});

it('refuses an override that does not say what it replaced, or says it badly', function (array $replaced, string $field): void {
    expect(static fn(): string => whoPutIt(['origin' => 'overridden', 'named' => 'plex', ...$replaced]))
        ->toThrow(OriginIsUnreadable::class, sprintf('`%s`', $field));
})->with([
    [[], 'replaced'],
    [['replaced' => 'the default'], 'replaced'],
    [['replaced' => ['withheld' => false]], 'from'],
    [['replaced' => ['withheld' => false, 'from' => 'bundled']], 'from'],
    [['replaced' => ['from' => ['origin' => 'bundled']]], 'withheld'],
    [['replaced' => ['withheld' => 'no', 'from' => ['origin' => 'bundled']]], 'withheld'],
    [['replaced' => ['withheld' => false, 'value' => 8096, 'from' => ['origin' => 'bundled']]], 'value'],
    [['replaced' => ['withheld' => false, 'from' => ['origin' => 'inherited']]], 'inherited'],
]);

it('refuses a replaced value that is there and blank, keeping the kernel refusal underneath', function (): void {
    try {
        whoPutIt(['origin' => 'overridden', 'named' => 'plex', 'replaced' => ['withheld' => false, 'value' => ' ', 'from' => ['origin' => 'bundled']]]);
        $underneath = null;
    } catch (OriginIsUnreadable $why) {
        $underneath = $why->getPrevious();
    }

    expect($underneath)->toBeInstanceOf(AnOriginIsUnnamed::class);
});

it('lets the word decide the arm, whatever else the table carries', function (): void {
    // A bundled table carrying a name is bundled. Read by which field is
    // present, it would be a plugin attribution the stack never made.
    expect(whoPutIt(['origin' => 'bundled', 'named' => 'plex']))->toBe('bundled')
        ->and(whoPutIt(['origin' => 'operator', 'why' => 'a reason']))->toBe('operator');
});

it('refuses a row with no origin, and one whose origin is not a table', function (): void {
    // Never read as bundled: that is the one repair the rule forbids.
    expect(static fn(): WhoPutItThere => Attributions::of([]))
        ->toThrow(OriginIsUnreadable::class, '`origin`')
        ->and(static fn(): string => whoPutIt('bundled'))
        ->toThrow(OriginIsUnreadable::class, '`origin`')
        ->and(static fn(): string => whoPutIt(['origin' => 7]))
        ->toThrow(OriginIsUnreadable::class, '`origin`')
        ->and(static fn(): string => whoPutIt(['named' => 'plex']))
        ->toThrow(OriginIsUnreadable::class, '`origin`');
});

it('refuses a word it has not been taught, quoting it beside the words it reads', function (): void {
    // The word is what finds the release that added it, and the list is taken
    // from the enum, so it names every one.
    expect(static fn(): string => whoPutIt(['origin' => 'inherited']))
        ->toThrow(OriginIsUnreadable::class, 'It attributes this to `inherited`, and this app reads `bundled`, `operator`, `plugin`, `unknown`, `overridden`, `orphaned`.');
});

it('refuses a plugin with no name, and an unknown with no reason', function (): void {
    expect(static fn(): string => whoPutIt(['origin' => 'plugin']))
        ->toThrow(OriginIsUnreadable::class, '`named`')
        ->and(static fn(): string => whoPutIt(['origin' => 'plugin', 'named' => 7]))
        ->toThrow(OriginIsUnreadable::class, '`named`')
        ->and(static fn(): string => whoPutIt(['origin' => 'unknown']))
        ->toThrow(OriginIsUnreadable::class, '`why`')
        ->and(static fn(): string => whoPutIt(['origin' => 'unknown', 'why' => false]))
        ->toThrow(OriginIsUnreadable::class, '`why`')
        ->and(static fn(): string => whoPutIt(['origin' => 'orphaned']))
        ->toThrow(OriginIsUnreadable::class, '`named`')
        ->and(static fn(): string => whoPutIt(['origin' => 'overridden', 'replaced' => ['withheld' => false, 'from' => ['origin' => 'bundled']]]))
        ->toThrow(OriginIsUnreadable::class, '`named`');
});

it('refuses a blank name or reason as its own refusal, keeping the kernel one underneath', function (): void {
    // Turned into this reader's refusal so every envelope's own covers it. An
    // adapter that caught only its envelope's refusal would otherwise let a
    // stack's blank plugin name end the screen it was reading for.
    $blank = static function (array $origin): ?Throwable {
        try {
            whoPutIt($origin);
        } catch (OriginIsUnreadable $why) {
            return $why->getPrevious();
        }

        return null;
    };

    expect($blank(['origin' => 'plugin', 'named' => '  ']))->toBeInstanceOf(AnOriginIsUnnamed::class)
        ->and($blank(['origin' => 'unknown', 'why' => ' ']))->toBeInstanceOf(AnOriginIsUnnamed::class)
        ->and(static fn(): string => whoPutIt(['origin' => 'plugin', 'named' => '  ']))
        ->toThrow(OriginIsUnreadable::class, 'It names nobody: something was attributed to a plugin with no name');
});
