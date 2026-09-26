<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\HandoverSaysNothing;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\TheFilesTouched;
use Modules\Kernel\Api\WhatTheHandoverDid;

/** One word carried out of whichever arm `writesTo()` takes. */
final readonly class WhereTheHandoverSaidItWrites
{
    public function __construct(public string $said) {}
}

/** Where a handover says the command's words go, or the word for it not saying. Named for this file (`G10`). */
function whereTheHandoverSaysItWrites(WhatTheHandoverDid $did): string
{
    return $did->writesTo(
        there: static fn(string $where): WhereTheHandoverSaidItWrites => new WhereTheHandoverSaidItWrites($where),
        unsaid: static fn(): WhereTheHandoverSaidItWrites => new WhereTheHandoverSaidItWrites('not said'),
    )->said;
}

it('carries an install as the stack reported it, less the space around each word', function (): void {
    $did = WhatTheHandoverDid::installing(
        name: ' watch ',
        rehearsed: false,
        started: true,
        standing: HowItIsHosted::Hosted,
        output: ' /home/me/.lemonfiber/hosted/watch.log ',
        touched: TheFilesTouched::these(' /home/me/.config/systemd/user/lemonfiber-watch.service '),
    );

    expect($did->did())->toBe(HandingOver::Install)
        ->and($did->name())->toBe('watch')
        ->and($did->wasRehearsed())->toBeFalse()
        ->and($did->started())->toBeTrue()
        ->and($did->standing())->toBe(HowItIsHosted::Hosted)
        ->and(whereTheHandoverSaysItWrites($did))->toBe('/home/me/.lemonfiber/hosted/watch.log')
        ->and(iterator_to_array($did->touched(), preserve_keys: false))->toBe(['/home/me/.config/systemd/user/lemonfiber-watch.service']);
});

it('says the stack did not say where an install writes, rather than a blank path', function (): void {
    $did = WhatTheHandoverDid::installingWithNowhereSaid(
        name: 'watch',
        rehearsed: false,
        started: false,
        standing: HowItIsHosted::InstalledUnverified,
        touched: TheFilesTouched::these(),
    );

    expect(whereTheHandoverSaysItWrites($did))->toBe('not said')
        ->and($did->started())->toBeFalse()
        ->and($did->standing())->toBe(HowItIsHosted::InstalledUnverified)
        ->and(iterator_to_array($did->touched(), preserve_keys: false))->toBe([]);
});

it('carries a removal, which started nothing and writes nowhere, with its files in order', function (): void {
    $did = WhatTheHandoverDid::removing(
        name: 'boot',
        rehearsed: false,
        standing: HowItIsHosted::NotHosted,
        touched: TheFilesTouched::these('/a/two.plist', '/a/one.plist'),
    );

    expect($did->did())->toBe(HandingOver::Remove)
        ->and($did->name())->toBe('boot')
        ->and($did->started())->toBeFalse()
        ->and($did->standing())->toBe(HowItIsHosted::NotHosted)
        ->and(whereTheHandoverSaysItWrites($did))->toBe('not said')
        ->and(iterator_to_array($did->touched(), preserve_keys: false))->toBe(['/a/two.plist', '/a/one.plist']);
});

it('carries a rehearsal as one, whichever act it was', function (): void {
    $install = WhatTheHandoverDid::installingWithNowhereSaid(
        name: 'watch',
        rehearsed: true,
        started: false,
        standing: HowItIsHosted::NotHosted,
        touched: TheFilesTouched::these(),
    );
    $removal = WhatTheHandoverDid::removing(name: 'watch', rehearsed: true, standing: HowItIsHosted::Hosted, touched: TheFilesTouched::these());

    expect($install->wasRehearsed())->toBeTrue()
        ->and($removal->wasRehearsed())->toBeTrue();
});

it('refuses a blank command, a blank file and a blank place to write', function (): void {
    expect(static fn(): WhatTheHandoverDid => WhatTheHandoverDid::removing(
        name: ' ',
        rehearsed: false,
        standing: HowItIsHosted::NotHosted,
        touched: TheFilesTouched::these(),
    ))->toThrow(HandoverSaysNothing::class, 'which command')
        ->and(static fn(): WhatTheHandoverDid => WhatTheHandoverDid::removing(
            name: 'watch',
            rehearsed: false,
            standing: HowItIsHosted::NotHosted,
            touched: TheFilesTouched::these('/a', ' '),
        ))->toThrow(HandoverSaysNothing::class, 'which file it touched')
        ->and(static fn(): WhatTheHandoverDid => WhatTheHandoverDid::installing(
            name: 'watch',
            rehearsed: false,
            started: true,
            standing: HowItIsHosted::Hosted,
            output: "\t",
            touched: TheFilesTouched::these(),
        ))->toThrow(HandoverSaysNothing::class, 'where watch writes its words');
});
