<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\ACredentialRevoke;
use Modules\Kernel\Api\ACredentialRevokeAgreed;
use Modules\Kernel\Api\ACredentialRevokeWasNotRead;
use Modules\Kernel\Api\AGrantAsked;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\CredentialGrantSaysNothing;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheCredentialGrants;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\TheGrantsAsked;
use Modules\Kernel\Api\WhatARevokeCameTo;
use Modules\Kernel\Api\WhatTheStoreProtects;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** The Plex token granted to this plugin. */
function thePlexTokenGrantedTo(string $plugin): ACredentialGrant
{
    return ACredentialGrant::of(APluginId::called($plugin), 'plex.token', ServiceId::called('plex'), 'library-scan', AMomentAsWritten::inSeconds('1761321600'));
}

it('keeps the plugin, the credential, whose it is, what it fills and when it was approved', function (): void {
    $grant = thePlexTokenGrantedTo('tdarr');
    $unstamped = ACredentialGrant::of(APluginId::called('tdarr'), 'plex.token', ServiceId::called('plex'), 'library-scan', AMomentAsWritten::inSeconds(''));

    expect([$grant->plugin()->named(), $grant->credential(), $grant->whose()->named(), $grant->fills(), whenItWasApproved($grant)])
        ->toBe(['tdarr', 'plex.token', 'plex', 'library-scan', '1761321600'])
        ->and(whenItWasApproved($unstamped))->toBe('unsaid');
});

it('refuses a grant that will not say which credential, or what for', function (string $credential, string $fills, string $field): void {
    expect(fn(): ACredentialGrant => ACredentialGrant::of(APluginId::called('tdarr'), $credential, ServiceId::called('plex'), $fills, AMomentAsWritten::inSeconds('')))
        ->toThrow(CredentialGrantSaysNothing::class, sprintf('`%s`', $field));
})->with([
    ['', 'library-scan', 'credential'],
    ['plex.token', ' ', 'for'],
]);

it('is the same grant only for the same credential to the same plugin', function (): void {
    $other = ACredentialGrant::of(APluginId::called('tdarr'), 'jellyfin.key', ServiceId::called('jellyfin'), 'library-scan', AMomentAsWritten::inSeconds(''));

    expect(thePlexTokenGrantedTo('tdarr')->isTheSameAs(thePlexTokenGrantedTo('tdarr')))->toBeTrue()
        ->and(thePlexTokenGrantedTo('tdarr')->isTheSameAs(thePlexTokenGrantedTo('bazarr')))->toBeFalse()
        ->and(thePlexTokenGrantedTo('tdarr')->isTheSameAs($other))->toBeFalse();
});

it('keeps grants in the stack\'s order', function (): void {
    $grants = TheCredentialGrants::of(...['first' => thePlexTokenGrantedTo('tdarr'), 'second' => thePlexTokenGrantedTo('bazarr')]);

    expect($grants)->toHaveCount(2)
        ->and(array_keys(iterator_to_array($grants, preserve_keys: true)))->toBe([0, 1]);
});

it('reads a revoke apart from one that ended, and a yes only against a reading of that grant', function (): void {
    $restarts = Services::these(ServiceId::called('tdarr'));
    $read = ACredentialRevoke::read(' revoke-1 ', thePlexTokenGrantedTo('tdarr'), $restarts);
    $ended = ACredentialRevoke::ended(' revoke-1 ', thePlexTokenGrantedTo('tdarr'), $restarts, ' It was stopped ');
    $agreed = ACredentialRevokeAgreed::after($read, thePlexTokenGrantedTo('tdarr'));

    expect([$read->agreement(), $read->wasEnded(), $read->unrestarted(), $read->restarts()])->toBe(['revoke-1', false, '', $restarts])
        ->and([$ended->agreement(), $ended->wasEnded(), $ended->unrestarted()])->toBe(['revoke-1', true, 'It was stopped'])
        ->and($read->readsARevokeOf(thePlexTokenGrantedTo('tdarr')))->toBeTrue()
        ->and($read->readsARevokeOf(thePlexTokenGrantedTo('bazarr')))->toBeFalse()
        ->and($ended->readsARevokeOf(thePlexTokenGrantedTo('tdarr')))->toBeFalse()
        ->and(ACredentialRevoke::read(' ', thePlexTokenGrantedTo('tdarr'), $restarts)->readsARevokeOf(thePlexTokenGrantedTo('tdarr')))->toBeFalse()
        ->and([$agreed->agreement(), $agreed->grant()->credential()])->toBe(['revoke-1', 'plex.token']);
});

it('refuses a yes to ending a grant with no reading of it to quote', function (): void {
    $ended = ACredentialRevoke::ended('revoke-1', thePlexTokenGrantedTo('tdarr'), Services::none(), '');

    expect(fn(): ACredentialRevokeAgreed => ACredentialRevokeAgreed::after($ended, thePlexTokenGrantedTo('tdarr')))
        ->toThrow(ACredentialRevokeWasNotRead::class, 'Ending the grant of `plex.token` to `tdarr` was agreed to with no reading of it to quote.');
});

it('carries a revoke beside the credentials and grants the stack answered it with', function (): void {
    $grants = TheCredentialGrants::of(thePlexTokenGrantedTo('tdarr'));
    $held = TheCredentialsHeld::of(WhatTheStoreProtects::said('Plain files', Remarks::of(), Remarks::of()), $grants);
    $revoke = ACredentialRevoke::read('revoke-1', thePlexTokenGrantedTo('tdarr'), Services::none());
    $came = WhatARevokeCameTo::of($revoke, $held);

    expect($held->grants())->toBe($grants)
        ->and($came->revoke())->toBe($revoke)
        ->and($came->held())->toBe($held);
});

/** When a grant was approved, in seconds since the epoch, or that the stack did not say. */
function whenItWasApproved(ACredentialGrant $grant): string
{
    return $grant->approved()->read(
        read: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()),
        unreadable: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unsaid'),
    )->said;
}

it('asks for a grant by the line the stack names it with, and refuses one with no line', function (): void {
    $asked = AGrantAsked::of(thePlexTokenGrantedTo('tdarr'), 'plex.token@tdarr');
    $granting = TheGrantsAsked::these(...['first' => $asked, 'second' => AGrantAsked::of(thePlexTokenGrantedTo('bazarr'), 'plex.token@bazarr')]);

    expect([$asked->grant()->credential(), $asked->approval()])->toBe(['plex.token', 'plex.token@tdarr'])
        ->and(iterator_to_array($granting->approvals(), preserve_keys: false))->toBe(['plex.token@tdarr', 'plex.token@bazarr'])
        ->and($granting)->toHaveCount(2)
        ->and(array_keys(iterator_to_array($granting, preserve_keys: true)))->toBe([0, 1])
        ->and(fn(): AGrantAsked => AGrantAsked::of(thePlexTokenGrantedTo('tdarr'), ' '))->toThrow(CredentialGrantSaysNothing::class, '`approval`');
});
