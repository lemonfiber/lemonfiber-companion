<?php

declare(strict_types=1);

use Modules\Kernel\Api\WhetherItIsOffered;
use Modules\Operator\Internal\ViewModels\AnOffer;

it('folds each answer into whether it presses, what it says and whether it leads to updates', function (WhetherItIsOffered $whether, bool $offers, string $note, string $updatesAt): void {
    $offer = AnOffer::of($whether, '/stacks/a/updates');

    expect([$offer->offers, $offer->note, $offer->updatesAt])->toBe([$offers, $note, $updatesAt]);
})->with([
    'offered' => [WhetherItIsOffered::Offered, true, '', ''],
    'not set up' => [WhetherItIsOffered::NotSetUp, true, 'connection.not_set_up', ''],
    'not theirs' => [WhetherItIsOffered::NotTheirs, false, 'connection.not_for_this_account', ''],
    'too old' => [WhetherItIsOffered::NeedsANewerLemonfiber, false, 'connection.not_on_this_stack', '/stacks/a/updates'],
    'not known' => [WhetherItIsOffered::NotKnown, true, '', ''],
]);
