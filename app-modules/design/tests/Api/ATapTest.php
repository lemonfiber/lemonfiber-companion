<?php

declare(strict_types=1);

namespace Modules\Design\Tests\Api;

use function e;
use function expect;
use function html_entity_decode;
use function it;

use Modules\Design\Api\ATap;
use Native\Mobile\Edge\CallbackRegistry;

it('hands the method every value as it was written, whatever it holds', function (int|string ...$with): void {
    $written = ATap::calling('choose', ...$with)->written();

    expect(CallbackRegistry::parse(html_entity_decode(e($written))))->toBe(['method' => 'choose', 'args' => $with]);
})->with([
    'a plain name' => ['den'],
    'a quote that would close its argument' => ["den', 'den"],
    'double quotes and a backslash' => ['the "den" \\ reader'],
    'markup and an ampersand' => ['<b>den</b> & co'],
    'parentheses and a comma' => ['den), wipe(1'],
    'letters past ASCII' => ['Sanne’s kamer — ☕'],
    'a row and a name' => [2, "o'neil"],
    'an empty string' => [''],
]);

it('writes a call with nothing to hand as a bare call', function (): void {
    expect(ATap::calling('again')->written())->toBe('again()');
});

it('leaves nothing in a string the markup or the parse reads', function (): void {
    expect(ATap::calling('choose', "'\"<>&")->written())->toBe("choose('\\u0027\\u0022\\u003C\\u003E\\u0026')");
});
