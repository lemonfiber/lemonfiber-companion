<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ACertificateReplacement;
use Modules\Kernel\Api\ACertificateReplacementAgreed;
use Modules\Kernel\Api\ACertificateReplacementWasNotRead;
use Modules\Kernel\Api\AHolderReached;
use Modules\Kernel\Api\CredentialSaysNothing;
use Modules\Kernel\Api\HowFarAHolderWasReached;
use Modules\Kernel\Api\WhereAReplacementSettled;

use function sprintf;

it('reads what a replacement would do under the name a yes quotes, holding no holder yet', function (): void {
    $reading = ACertificateReplacement::read(' an-offer ', ' the decline service is handed it ');

    expect([$reading->agreement(), $reading->said(), $reading->settledAt(), count($reading->holders()), $reading->isAReading(), $reading->landed()])
        ->toBe(['an-offer', 'the decline service is handed it', WhereAReplacementSettled::Rehearsed, 0, true, false]);
});

it('is no reading a yes may quote where the stack named no offer', function (): void {
    expect(ACertificateReplacement::read(' ', 'it would be replaced')->isAReading())->toBeFalse()
        ->and(fn(): ACertificateReplacementAgreed => ACertificateReplacementAgreed::after(ACertificateReplacement::read('', 'it would be replaced')))->toThrow(ACertificateReplacementWasNotRead::class);
});

it('lands only where the stack says it was replaced, holding each holder in order and no offer', function (WhereAReplacementSettled $settled, bool $landed): void {
    $updated = AHolderReached::said('the decline service', HowFarAHolderWasReached::Updated, '');
    $failed = AHolderReached::said('the request gate', HowFarAHolderWasReached::Failed, ' it did not answer ');
    $came = ACertificateReplacement::settled($settled, ' said ', $updated, $failed);

    expect([$came->landed(), $came->isAReading(), $came->agreement(), $came->said(), iterator_to_array($came->holders(), preserve_keys: false)])->toBe([$landed, false, '', 'said', [$updated, $failed]])
        ->and($failed->detail())->toBe('it did not answer')
        ->and([$updated->holder(), $updated->reach(), $updated->detail()])->toBe(['the decline service', HowFarAHolderWasReached::Updated, '']);
})->with([
    'replaced' => [WhereAReplacementSettled::Replaced, true],
    'replaced, unproven' => [WhereAReplacementSettled::ReplacedUnproven, false],
    'unproven' => [WhereAReplacementSettled::Unproven, false],
    'refused' => [WhereAReplacementSettled::Refused, false],
    'unknown' => [WhereAReplacementSettled::Unknown, false],
    'elsewhere' => [WhereAReplacementSettled::Elsewhere, false],
    'rehearsed, with no offer to quote' => [WhereAReplacementSettled::Rehearsed, false],
]);

it('agrees to a reading by its name, and to nothing that was carried out', function (): void {
    expect(ACertificateReplacementAgreed::after(ACertificateReplacement::read('an-offer', 'it would be replaced'))->agreement())->toBe('an-offer')
        ->and(fn(): ACertificateReplacementAgreed => ACertificateReplacementAgreed::after(ACertificateReplacement::settled(WhereAReplacementSettled::Replaced, 'it was')))
        ->toThrow(ACertificateReplacementWasNotRead::class, 'agreed to with no reading of it to quote');
});

it('refuses a holder with no name, and one short of holding the new certificate that says nothing of why', function (HowFarAHolderWasReached $reach, string $holder, string $detail, string $blank): void {
    expect(fn(): AHolderReached => AHolderReached::said($holder, $reach, $detail))->toThrow(CredentialSaysNothing::class, sprintf('`%s` blank', $blank));
})->with([
    'no name' => [HowFarAHolderWasReached::Updated, ' ', '', 'consumer'],
    'pending, saying nothing' => [HowFarAHolderWasReached::Pending, 'the request gate', ' ', 'detail'],
    'failed, saying nothing' => [HowFarAHolderWasReached::Failed, 'the request gate', '', 'detail'],
]);

it('names a catalogue key for every outcome and every reach', function (): void {
    expect(WhereAReplacementSettled::ReplacedUnproven->saidOnTheScreen())->toBe('stacks.credentials.replace.settled.replaced-unproven')
        ->and(HowFarAHolderWasReached::Pending->saidOnTheScreen())->toBe('stacks.credentials.replace.reach.pending');
});
