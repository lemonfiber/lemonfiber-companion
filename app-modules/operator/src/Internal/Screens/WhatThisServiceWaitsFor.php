<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function array_keys;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingWhatServicesRead;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\Among;
use Modules\Operator\Internal\ASettingBeingChanged;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\ChangesASetting;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowWhatAServiceWaitsForReads;
use Modules\Operator\Internal\ViewModels\WhatThisServiceWaitsForShows;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * Everything one service the stack holds back waits for, and giving it the settings among that.
 *
 * The service waits rather than fails, so it is shown with what it waits for
 * and none of it by value. Each setting among that is changed here as on the
 * screen that lists it, through {@see ChangesASetting}, and the rehearsal says
 * which services giving it starts; a secret is said to be entered at the
 * stack's terminal; the service it runs through is named as waiting itself.
 * A service no longer held back is said to wait for nothing.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatThisServiceWaitsFor extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;
    use ChangesASetting;

    public const string TEMPLATE = 'operator::what-this-service-waits-for';

    /** Every setting and secret, and every service held back, as last read or heard. */
    public ?TheServicesSettings $read = null;

    /** What the service waits for, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?WhatThisServiceWaitsForShows $answered = null;

    public function __construct(
        protected readonly SettingWhatServicesRead $tuning,
        protected readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** The service this page is about. */
    public function service(): ServiceId
    {
        return ServiceId::called($this->around->aboutOn($this));
    }

    /** Read the settings again. */
    public function again(): void
    {
        $this->answered = null;
        $this->read = null;
    }

    /** What the service waits for, asked once per frame after where a change has got to and what the stream said. */
    public function answer(): WhatThisServiceWaitsForShows
    {
        $this->howItIsGoing();
        $this->hearTheSettings();
        $read = $this->read;

        if ($read instanceof TheServicesSettings) {
            return new HowWhatAServiceWaitsForReads()->these($read, $this->service(), $this->alongside());
        }

        return $this->answered ??= $this->asked();
    }

    /** A setting the service waits for, or one that hangs together with one it waits for, as last read; a secret is never one. */
    protected function itsSetting(string $setting): ?ASetting
    {
        $read = $this->read;
        $waiting = $read instanceof TheServicesSettings ? Among::theWaiting($read->waiting(), $this->service()) : null;
        $held = $read instanceof TheServicesSettings ? Among::theSettings($read->settings(), $setting) : null;

        if (! $waiting instanceof AServiceWaiting || ! $held instanceof ASetting) {
            return null;
        }

        foreach ($read->settings() as $one) {
            if ($waiting->waitsFor($one) && ($one->name() === $held->name() || $one->hangsTogetherWith($held))) {
                return $held;
            }
        }

        return null;
    }

    /**
     * The settings the open change moves alongside the one it opened.
     *
     * @return list<string>
     */
    private function alongside(): array
    {
        return $this->changing instanceof ASettingBeingChanged ? array_keys($this->changing->alongside) : [];
    }

    /** The settings, read, with what the service waits for among them. */
    private function asked(): WhatThisServiceWaitsForShows
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisServiceWaitsForShows => $this->tuning->readOn($stack, $session)->either(
                found: function (TheServicesSettings $settings): WhatThisServiceWaitsForShows {
                    $this->read = $settings;

                    return new HowWhatAServiceWaitsForReads()->these($settings, $this->service(), $this->alongside());
                },
                met: $this->lettingGoIfRefused($stack, new HowWhatAServiceWaitsForReads()->met(...)),
            ),
            notHeld: static fn(): WhatThisServiceWaitsForShows => new HowWhatAServiceWaitsForReads()->signedOut(),
        );
    }
}
