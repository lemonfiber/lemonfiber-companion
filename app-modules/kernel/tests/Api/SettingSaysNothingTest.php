<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use Closure;

use function expect;
use function it;

use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\SettingSaysNothing;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\WhoseASettingIs;

use function sprintf;

it('refuses a setting or a secret with a word it owes left blank, naming the word', function (Closure $made, string $word): void {
    expect($made)->toThrow(SettingSaysNothing::class, sprintf('`%s`', $word));
})->with([
    'a setting with no name' => [static fn(): object => ASetting::read(' ', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing()), 'name'],
    'a secret with no name' => [static fn(): object => ASecret::read(' ', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), held: false, from: WhereASecretCameFrom::Unsaid), 'name'],
    'a reader with no variable' => [static fn(): object => AServiceReadingIt::of(ServiceId::called('caddy'), ' ', ASettingValue::nothing()), 'variable'],
    'a namespace with no name' => [static fn(): object => WhoseASettingIs::theNamespaces(' '), 'namespace'],
    'a change to no setting' => [static fn(): object => ASettingChange::toHold(' ', 'Germany'), 'setting'],
    'putting no setting back' => [static fn(): object => ASettingChange::backToDefault(''), 'setting'],
]);
