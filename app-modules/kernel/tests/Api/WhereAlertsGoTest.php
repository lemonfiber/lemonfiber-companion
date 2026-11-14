<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AlertBackEndId;
use Modules\Kernel\Api\AnAlertBackEnd;
use Modules\Kernel\Api\AnUnreadBackEnd;
use Modules\Kernel\Api\HowADeliveryEnded;
use Modules\Kernel\Api\HowFarItIsProven;
use Modules\Kernel\Api\HowItsDeliveriesWent;
use Modules\Kernel\Api\KindOfAlertBackEnd;
use Modules\Kernel\Api\WhereAlertsGoSaysNothing;
use Modules\Kernel\Api\WhereItIsHosted;
use Modules\Kernel\Api\WhoChoseIt;

it('refuses a blank where an operator has to read something', function (callable $built, string $part): void {
    expect($built)->toThrow(WhereAlertsGoSaysNothing::class, $part);
})->with([
    'an id' => [static fn(): AlertBackEndId => AlertBackEndId::called(' '), 'a back-end\'s id'],
    'a member' => [static fn(): WhoChoseIt => WhoChoseIt::aMember(' '), 'the member who chose a back-end'],
    'why it is held' => [static fn(): HowFarItIsProven => HowFarItIsProven::held(' '), 'why a back-end is held'],
    'why it failed' => [static fn(): HowADeliveryEnded => HowADeliveryEnded::failed(' '), 'why a delivery failed'],
    'a host' => [static fn(): WhereItIsHosted => WhereItIsHosted::at(' '), 'a hosted back-end\'s host'],
    'why it went unread' => [static fn(): AnUnreadBackEnd => AnUnreadBackEnd::because(AlertBackEndId::called('ntfy'), ' '), 'why a back-end could not be read'],
    'where it delivers' => [static fn(): AnAlertBackEnd => AnAlertBackEnd::configured(
        AlertBackEndId::called('ntfy'),
        KindOfAlertBackEnd::Ntfy,
        ' ',
        WhoChoseIt::theOperator(),
        WhereItIsHosted::notHosted(),
        HowFarItIsProven::unproven(),
        HowItsDeliveriesWent::nothingYet(),
    ), 'where a back-end delivers'],
]);

it('tells one back-end from another by its id', function (): void {
    expect(AlertBackEndId::called('ntfy')->isTheSameAs(AlertBackEndId::called('ntfy')))->toBeTrue()
        ->and(AlertBackEndId::called('ntfy')->isTheSameAs(AlertBackEndId::called('gotify')))->toBeFalse();
});
