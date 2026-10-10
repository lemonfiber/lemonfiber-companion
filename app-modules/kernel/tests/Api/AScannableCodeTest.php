<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\AnInvitationToPassOn;
use Modules\Kernel\Api\AScannableCode;
use Modules\Kernel\Api\CodeIsNotSquare;
use Modules\Kernel\Api\InvitationSaysNothing;
use Modules\Kernel\Api\TheDoorSaysNothing;

it('keeps a square of rows, top to bottom, and counts its side', function (): void {
    $code = AScannableCode::drawn(...['b' => '10', 'a' => '01']);

    expect(iterator_to_array($code, preserve_keys: true))->toBe(['10', '01'])
        ->and($code)->toHaveCount(2);
});

it('holds no rows where no code could be drawn', function (): void {
    expect(iterator_to_array(AScannableCode::none(), preserve_keys: true))->toBe([])
        ->and(AScannableCode::none())->toHaveCount(0);
});

it('refuses rows that are not a square of dark and light', function (): void {
    expect(fn(): AScannableCode => AScannableCode::drawn('10', '0'))->toThrow(CodeIsNotSquare::class, 'A scannable code of 2 rows has a row that is not 2 squares')
        ->and(fn(): AScannableCode => AScannableCode::drawn('101', '010'))->toThrow(CodeIsNotSquare::class)
        ->and(fn(): AScannableCode => AScannableCode::drawn('12', '01'))->toThrow(CodeIsNotSquare::class)
        ->and(fn(): AScannableCode => AScannableCode::drawn('x10', '010', '101'))->toThrow(CodeIsNotSquare::class)
        ->and(fn(): AScannableCode => AScannableCode::drawn("10\n", '01', '11'))->toThrow(CodeIsNotSquare::class);
});

it('puts the address and its caution under the covering sentence, and the caution only where there is one', function (): void {
    $cautioned = AnInvitationToHand::to('anna', AnAddressToHand::at('http://192.168.1.42:8096', 'The number can change'), 72);
    $plain = AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 72);

    expect(AnInvitationToPassOn::of($cautioned, 'Come in', 'Join:', 'Turn it down:')->text())->toBe("Come in\n\nhttp://192.168.1.42:8096\n\nThe number can change")
        ->and(AnInvitationToPassOn::of($plain, 'Come in', 'Join:', 'Turn it down:')->text())->toBe("Come in\n\nhttp://loft.local:8096")
        ->and(AnInvitationToPassOn::of($plain, 'Come in', 'Join:', 'Turn it down:')->named())->toBe('anna')
        ->and(fn(): AnInvitationToPassOn => AnInvitationToPassOn::of($plain, ' ', 'Join:', 'Turn it down:'))->toThrow(InvitationSaysNothing::class, 'its `covering` blank')
        ->and(fn(): AnInvitationToPassOn => AnInvitationToPassOn::of($plain, 'Come in', 'Join:', ' '))->toThrow(InvitationSaysNothing::class, 'its `declining` blank')
        ->and(fn(): AnInvitationToPassOn => AnInvitationToPassOn::of($plain, 'Come in', ' ', 'Turn it down:'))->toThrow(InvitationSaysNothing::class, 'its `joining` blank');
});

it('ends the text with the address that turns the invitation down, on a line of its own under its sentence, and keeps it out of the code', function (): void {
    $address = AnAddressToHand::declinable('http://192.168.1.42:8096', 'The number can change', 'http://192.168.1.42:5056/decline/abc');
    $invitation = AnInvitationToPassOn::of(AnInvitationToHand::to('anna', $address, 72), 'Come in', 'Join:', 'Turn it down:');

    expect($invitation->text())->toBe("Come in\n\nhttp://192.168.1.42:8096\n\nThe number can change\n\nTurn it down:\nhttp://192.168.1.42:5056/decline/abc")
        ->and($address->carried())->toBe('http://192.168.1.42:8096')
        ->and($address->decline())->toBe('http://192.168.1.42:5056/decline/abc')
        ->and(AnAddressToHand::at('http://loft.local:8096', '')->decline())->toBe('')
        ->and(fn(): AnAddressToHand => AnAddressToHand::declinable('http://192.168.1.42:8096', '', ' '))->toThrow(TheDoorSaysNothing::class, '`decline`');
});

it('puts the join link after the address and before the address that turns it down, each under its sentence, and draws each as a code of its own', function (): void {
    $address = AnAddressToHand::joinable(AnAddressToHand::declinable('http://192.168.1.42:8096', '', 'http://192.168.1.42:5056/decline/abc'), 'lemonfiber://join?stack=abc');
    $invitation = AnInvitationToPassOn::of(AnInvitationToHand::to('anna', $address, 72), 'Come in', 'Join:', 'Turn it down:');

    expect($invitation->text())->toBe("Come in\n\nhttp://192.168.1.42:8096\n\nJoin:\nlemonfiber://join?stack=abc\n\nTurn it down:\nhttp://192.168.1.42:5056/decline/abc")
        ->and($address->join())->toBe('lemonfiber://join?stack=abc')
        ->and($address->joining()->carried())->toBe('lemonfiber://join?stack=abc')
        ->and($address->declining()->carried())->toBe('http://192.168.1.42:5056/decline/abc')
        ->and($address->carried())->toBe('http://192.168.1.42:8096')
        ->and($address->whyNotJoinable())->toBe('')
        ->and(AnAddressToHand::at('http://loft.local:8096', '')->join())->toBe('')
        ->and(AnAddressToHand::at('http://loft.local:8096', '')->joining()->carried())->toBe('')
        ->and(fn(): AnAddressToHand => AnAddressToHand::joinable(AnAddressToHand::at('http://loft.local:8096', ''), ' '))->toThrow(TheDoorSaysNothing::class, '`join`');
});

it('keeps the stack\'s sentence for why an invitation has no join link, and puts no link in the text', function (): void {
    $address = AnAddressToHand::unjoinable(AnAddressToHand::at('http://loft.local:8096', ''), 'The house has no certificate to pin yet');

    expect($address->whyNotJoinable())->toBe('The house has no certificate to pin yet')
        ->and($address->join())->toBe('')
        ->and(AnInvitationToPassOn::of(AnInvitationToHand::to('anna', $address, 72), 'Come in', 'Join:', 'Turn it down:')->text())->toBe("Come in\n\nhttp://loft.local:8096")
        ->and(fn(): AnAddressToHand => AnAddressToHand::unjoinable(AnAddressToHand::at('http://loft.local:8096', ''), ''))->toThrow(TheDoorSaysNothing::class, '`unjoinable`');
});
