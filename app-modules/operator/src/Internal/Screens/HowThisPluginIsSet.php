<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingWhatServicesRead;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Operator\Internal\ASettingBeingChanged;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowAPluginsSettingsRead;
use Modules\Operator\Internal\Presenters\HowASettingChangeReads;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatAPluginsSettingsShow;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * One plugin's settings, secrets and grants, and changing a setting: rehearsed, agreed to, and followed.
 *
 * Every setting and secret one of the plugin's services reads is listed. A
 * setting is changed or put back to its default by a rehearsal first, and the
 * yes carries the offer that rehearsal answered. A secret is listed without its
 * value and is never changed here. The stream's settings replace what was read.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class HowThisPluginIsSet extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::how-this-plugin-is-set';

    /** What is typed into the open setting's field. */
    public string $typed = '';

    /** The setting open for changing, or null where none is. */
    public ?ASettingBeingChanged $changing = null;

    /** What the last rehearsal was asked with, which is what a yes agrees to. */
    public ?ASettingChange $asked = null;

    /** What the stack last reported, rehearsed or written. */
    public ?TheSettingAsChanged $report = null;

    /** The handle of the work being followed, while there is one. */
    public ?string $following = null;

    /** The plugin, once it was found among those installed. */
    public ?APlugin $plugin = null;

    /** Every setting and secret, as last read or heard. */
    public ?TheServicesSettings $read = null;

    /** The plugin's settings, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?WhatAPluginsSettingsShow $answered = null;

    /** Where changing a setting has got to, once this frame has asked. Public for the same reason. */
    public ?TheSettingChangeTurnedOutToBe $going = null;

    public function __construct(
        private readonly SettingWhatServicesRead $tuning,
        private readonly ExtendingTheStack $extending,
        private readonly Clock $clock,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}


    /** Read the plugin and its settings again. */
    public function again(): void
    {
        $this->answered = null;
        $this->plugin = null;
        $this->read = null;
    }

    /** Open one of the plugin's settings for changing, seeded with its value; any other name, a secret's among them, opens nothing. */
    public function change(string $setting): void
    {
        $held = $this->itsSetting($setting);

        if (is_string($this->following) || ! $held instanceof ASetting) {
            return;
        }

        $this->startAgain();
        $this->changing = new ASettingBeingChanged($setting, toDefault: false);
        $this->typed = $held->value()->said();
    }

    /** Close the open setting without asking the stack anything. */
    public function never(): void
    {
        $this->startAgain();
        $this->changing = null;
        $this->typed = '';
    }

    /** Leave what the change came to, keeping the setting open. */
    public function startAgain(): void
    {
        $this->asked = null;
        $this->report = null;
        $this->following = null;
        $this->going = null;
    }

    /** Ask what giving the open setting what is typed would do, writing nothing. */
    public function rehearse(): void
    {
        $open = $this->changing;

        if (is_string($this->following) || ! $open instanceof ASettingBeingChanged || $open->toDefault || ! $this->itsSetting($open->named) instanceof ASetting) {
            return;
        }

        $this->ask(ASettingChange::toHold($open->named, $this->typed));
    }

    /** Ask what putting one of the plugin's settings back to its default would do, writing nothing. */
    public function backToDefault(string $setting): void
    {
        if (is_string($this->following) || ! $this->itsSetting($setting) instanceof ASetting) {
            return;
        }

        $this->changing = new ASettingBeingChanged($setting, toDefault: true);
        $this->ask(ASettingChange::backToDefault($setting));
    }

    /** Make the change the rehearsal on the screen described; anywhere else, nothing is sent. */
    public function agree(): void
    {
        $asked = $this->asked;
        $report = $this->report;

        if (! $asked instanceof ASettingChange || ! $report instanceof TheSettingAsChanged || ! $report->rehearses($asked)) {
            return;
        }

        $agreed = ASettingChangeAgreed::after($report, $asked);
        $this->report = null;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->tuning->carryOut($stack, $session, $agreed));
    }

    /** Ask after the work again while the stack is at it, at {@see HowOftenAScreenLooks}'s interval. */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howItIsGoing()->stands->isWorking()) {
            $this->going = null;
        }
    }

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->howItIsGoing()->stands->isWorking();
    }

    /** The plugin's settings, asked once per frame, after where a change has got to and what the stream said. */
    public function answer(): WhatAPluginsSettingsShow
    {
        $this->howItIsGoing();
        $heard = $this->settingsHeard;

        if ($heard instanceof TheServicesSettings) {
            $this->settingsHeard = null;
            $this->read = $heard;
            $this->answered = null;
        }

        return $this->answered ??= $this->asked();
    }

    /** Where changing a setting has got to, asked once per frame. */
    public function howItIsGoing(): TheSettingChangeTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowASettingChangeReads()->notAsked()
            : $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->tuning->whatBecameOf($stack, $session, Job::named($following)));
    }

    private function ask(ASettingChange $change): void
    {
        $this->startAgain();
        $this->asked = $change;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->tuning->rehearse($stack, $session, $change));
    }

    /** The setting of that name one of the plugin's services reads, as last read, or none; a secret is never one. */
    private function itsSetting(string $setting): ?ASetting
    {
        $plugin = $this->plugin;
        $read = $this->read;
        $found = null;

        foreach ($plugin instanceof APlugin && $read instanceof TheServicesSettings ? $read->readByAnyOf($plugin->services())->settings() : [] as $held) {
            $found = $held->name() === $setting ? $held : $found;
        }

        return $found;
    }

    private function asked(): WhatAPluginsSettingsShow
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAPluginsSettingsShow => $this->plugin instanceof APlugin ? $this->settingsOf($this->plugin, $stack, $session) : $this->found($stack, $session),
            notHeld: static fn(): WhatAPluginsSettingsShow => new HowAPluginsSettingsRead()->signedOut(),
        );
    }

    /** The plugin, found among those installed, with its settings; one no longer installed is said as that. */
    private function found(Stack $stack, Session $session): WhatAPluginsSettingsShow
    {
        return $this->extending->installedOn($stack, $session)->either(
            found: function (ThePlugins $plugins) use ($stack, $session): WhatAPluginsSettingsShow {
                foreach ($plugins->installed() as $plugin) {
                    $this->plugin = $plugin->id() === $this->around->aboutOn($this) ? $plugin : $this->plugin;
                }

                return $this->plugin instanceof APlugin ? $this->settingsOf($this->plugin, $stack, $session) : new HowAPluginsSettingsRead()->notInstalled();
            },
            met: $this->lettingGoIfRefused($stack, new HowAPluginsSettingsRead()->met(...)),
        );
    }

    private function settingsOf(APlugin $plugin, Stack $stack, Session $session): WhatAPluginsSettingsShow
    {
        $read = $this->read;

        if ($read instanceof TheServicesSettings) {
            return new HowAPluginsSettingsRead()->these($plugin, $read, $this->clock->now());
        }

        return $this->tuning->readOn($stack, $session)->either(
            found: function (TheServicesSettings $settings) use ($plugin): WhatAPluginsSettingsShow {
                $this->read = $settings;

                return new HowAPluginsSettingsRead()->these($plugin, $settings, $this->clock->now());
            },
            met: $this->lettingGoIfRefused($stack, new HowAPluginsSettingsRead()->met(...)),
        );
    }

    /**
     * One act put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWork<TheSettingAsChanged> $asking
     */
    private function put(Closure $asking): TheSettingChangeTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheSettingChangeTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheSettingChangeTurnedOutToBe => new HowASettingChangeReads()->signedOut(),
        );
    }

    /**
     * What the stack said, as the screen draws it, holding what to follow or to agree to.
     *
     * A written change carries every setting after it, which replaces what was
     * read. A refusal reads the settings again, so a change agreed against a
     * value that moved is drawn against the one that stands.
     *
     * @param WhatBecameOfTheWork<TheSettingAsChanged> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheSettingChangeTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheSettingChangeTurnedOutToBe {
                $this->following = $job->shown();

                return new HowASettingChangeReads()->running();
            },
            answered: function (TheSettingAsChanged $report): TheSettingChangeTurnedOutToBe {
                $this->following = null;
                $this->report = $report;
                $this->read = $report->did() === WhatTheSettingRunDid::Rehearsed ? $this->read : $report->settings();
                $this->answered = null;

                return new HowASettingChangeReads()->answered($report);
            },
            ended: function (): TheSettingChangeTurnedOutToBe {
                $this->following = null;

                return new HowASettingChangeReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheSettingChangeTurnedOutToBe {
                $this->following = null;
                $this->read = null;
                $this->answered = null;

                return new HowASettingChangeReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheSettingChangeTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowASettingChangeReads()->met($why);
            },
        );
    }
}
