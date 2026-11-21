<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;

use ArrayObject;

use function in_array;
use function mb_strtoupper;

use Modules\Kernel\Api\ADecision;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\WhatKeepsRunning;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Kernel\Api\WhatSetupDecided;
use Modules\Kernel\Api\WhatWasFoundOfTheCredentials;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\HowADecisionIsChanged;
use Modules\Operator\Internal\TheCommandsAtTheMachine;
use Modules\Operator\Internal\ViewModels\ADecisionAsShown;
use Modules\Operator\Internal\ViewModels\AnOpeningAsShown;
use Modules\Operator\Internal\ViewModels\AutostartAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatAProtocolOpenedAsShown;
use Modules\Operator\Internal\ViewModels\WhatSetupDecidedAsShown;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Stacks\Api\AStacksScreen;

use function sprintf;
use function trim;

/** Every decision setup makes, read beside the settings store, the credentials and the hosting read, as the operator is shown them. */
final readonly class HowWhatSetupDecidedReads
{
    private const string COST = 'decided.cost.%s';

    private const string UNREAD = 'decided.autostart.unread';

    /**
     * @param array<string, ASetting>                $stored      every setting of the settings store, by name
     * @param array<string, WhereACredentialStands>  $stands      where each credential stands, by the setting it is kept in
     * @param array<string, Setting>                 $configured  every setting the configuration lists, by key
     * @param array<string, ADecision>               $decisions   every decision, by key
     * @param list<string>                           $refused     every name the stack lists as a credential, in capitals
     */
    private function __construct(
        private WhereAStackIs $goes,
        private array $stored,
        private array $stands,
        private array $configured,
        private array $decisions,
        private array $refused,
    ) {}

    public static function of(WhatSetupDecided $decided, WhatWasFoundOfTheSettings $stored, WhatWasFoundOfTheCredentials $credentials, WhereAStackIs $goes): self
    {
        $settings = $stored->either(
            found: static fn(TheServicesSettings $read): ArrayObject => new ArrayObject(self::settingsIn($read)),
            met: static fn(): ArrayObject => new ArrayObject(),
        )->getArrayCopy();
        $stands = $credentials->either(
            found: static fn(TheCredentialsHeld $held): ArrayObject => new ArrayObject(self::standingIn($held)),
            met: static fn(): ArrayObject => new ArrayObject(),
        )->getArrayCopy();
        $configured = [];

        foreach ($decided->settings() as $setting) {
            $configured[$setting->key] = $setting;
        }

        $decisions = [];

        foreach ($decided as $decision) {
            $decisions[$decision->key] = $decision;
        }

        return new self($goes, $settings, $stands, $configured, $decisions, self::refusedIn($decided, $stored, array_keys($stands)));
    }

    public static function signedOut(): WhatSetupDecidedAsShown
    {
        return new WhatSetupDecidedAsShown(HowTheReadingWent::theSessionEnded());
    }

    public static function waits(): WhatSetupDecidedAsShown
    {
        return new WhatSetupDecidedAsShown(HowTheReadingWent::itCameBack(), waits: true);
    }

    public static function met(Obstacle $why): WhatSetupDecidedAsShown
    {
        return new WhatSetupDecidedAsShown(HowTheReadingWent::somethingStopped($why));
    }

    public function shown(WhatKeepsRunning $hosting, ?WhatAChangeTurnedOutToBe $opened): WhatSetupDecidedAsShown
    {
        $shown = [];

        foreach ($this->decisions as $decision) {
            $shown[] = $this->decision($decision, $decision->affects);
        }

        return new WhatSetupDecidedAsShown(
            HowTheReadingWent::itCameBack(),
            $shown,
            new AutostartAsShown(
                $hosting->either(keeps: $this->autostartIn(...), met: static fn(): AsText => AsText::of(self::UNREAD))->said,
                TheCommandsAtTheMachine::installingAutostart(),
                TheCommandsAtTheMachine::removingAutostart(),
            ),
            $opened instanceof WhatAChangeTurnedOutToBe && $opened->comesTo->opens !== [] ? $this->opened($opened) : null,
            $this->refused,
        );
    }

    private function opened(WhatAChangeTurnedOutToBe $opened): WhatAProtocolOpenedAsShown
    {
        $steps = [];
        $proven = true;

        foreach ($opened->comesTo->opens as $opening) {
            $step = $this->opening($opening);
            $steps[] = $step;
            $proven = $proven && ($step->how !== HowADecisionIsChanged::AtTheMachine || $this->among($this->stands, $step->key) === WhereACredentialStands::Active);
        }

        return new WhatAProtocolOpenedAsShown($opened->key, $steps, $proven);
    }

    private function opening(AnOpeningAsShown $opening): ADecisionAsShown
    {
        return match (true) {
            $opening->setting === '' => new ADecisionAsShown($opening->what, $opening->because, '', isSet: false, costSaid: '', how: HowADecisionIsChanged::ToObtain, goes: '', command: '', standsSaid: '', setting: null),
            array_key_exists($opening->setting, $this->decisions) => $this->decision($this->decisions[$opening->setting], $opening->because),
            default => $this->configured($opening->setting, $opening->because, null),
        };
    }

    private function decision(ADecision $decision, string $affects): ADecisionAsShown
    {
        return match (true) {
            $decision->credential => $this->credential($decision->key, $affects, $decision->cost, $decision->held),
            $decision->setting !== null => $this->kept($decision, $decision->setting, $affects),
            default => $this->configured($decision->key, $affects, $decision->cost),
        };
    }

    private function configured(string $key, string $affects, ?Cost $cost): ADecisionAsShown
    {
        $setting = $this->among($this->configured, $key);

        if ($this->refuses($key) || ($setting instanceof Setting && $setting->holds->either(shown: static fn(): AsText => AsText::nothing(), withheld: static fn(): AsText => AsText::of($key))->said !== '')) {
            return $this->credential($key, $affects, $cost, $setting instanceof Setting);
        }

        $holds = $setting instanceof Setting ? $setting->holds->either(shown: AsText::of(...), withheld: AsText::nothing(...))->said : '';

        return new ADecisionAsShown($key, $affects, $holds, isSet: $setting instanceof Setting, costSaid: $this->cost($cost), how: HowADecisionIsChanged::Here, goes: '', command: '', standsSaid: '', setting: null);
    }

    private function kept(ADecision $decision, string $named, string $affects): ADecisionAsShown
    {
        $setting = $this->among($this->stored, $named);

        if (! $setting instanceof ASetting) {
            return new ADecisionAsShown($decision->key, $affects, '', isSet: $decision->held, costSaid: $this->cost($decision->cost), how: HowADecisionIsChanged::Here, goes: '', command: '', standsSaid: '', setting: $named);
        }

        $goes = $setting->whose()->either(
            core: fn(): AsText => AsText::of($this->goes->to(AStacksScreen::OwnSettings)),
            namespace: fn(string $plugin): AsText => AsText::of($this->goes->aPluginsSettings($plugin)),
        )->said;

        return new ADecisionAsShown($decision->key, $affects, $setting->value()->said(), isSet: $setting->value()->isHeld(), costSaid: $this->cost($decision->cost), how: HowADecisionIsChanged::Here, goes: $goes, command: '', standsSaid: '', setting: $named);
    }

    private function credential(string $key, string $affects, ?Cost $cost, bool $held): ADecisionAsShown
    {
        $stands = match ($this->among($this->stands, $key)) {
            null => $held ? 'decided.credential.set' : 'decided.credential.not_set',
            WhereACredentialStands::Absent => 'decided.credential.not_set',
            WhereACredentialStands::Invalid => 'decided.credential.refused',
            WhereACredentialStands::Active => 'decided.credential.proven',
            default => 'decided.credential.set',
        };

        return new ADecisionAsShown($key, $affects, '', isSet: $held, costSaid: $this->cost($cost), how: HowADecisionIsChanged::AtTheMachine, goes: '', command: TheCommandsAtTheMachine::settingACredential($key), standsSaid: $stands, setting: null);
    }

    /**
     * What is held under this name, or nothing where nothing is.
     *
     * @template T
     *
     * @param  array<string, T>  $held
     * @return T|null
     */
    private function among(array $held, string $name): mixed
    {
        if (array_key_exists($name, $held)) {
            return $held[$name];
        }

        return null;
    }

    private function refuses(string $key): bool
    {
        return in_array(mb_strtoupper($key), $this->refused, strict: true);
    }

    private function cost(?Cost $cost): string
    {
        return $cost instanceof Cost ? sprintf(self::COST, $cost->value) : '';
    }

    private function autostartIn(WhatRunsUnattended $running): AsText
    {
        $said = AsText::of(self::UNREAD);

        foreach ($running as $command) {
            $said = $command->name() === TheCommandsAtTheMachine::AUTOSTART ? AsText::of($command->standing()->saidOnTheScreen()) : $said;
        }

        return $said;
    }

    /** @return array<string, ASetting> */
    private static function settingsIn(TheServicesSettings $read): array
    {
        $settings = [];

        foreach ($read->settings() as $setting) {
            $settings[$setting->name()] = $setting;
        }

        return $settings;
    }

    /** @return array<string, WhereACredentialStands> */
    private static function standingIn(TheCredentialsHeld $held): array
    {
        $stands = [];

        foreach ($held as $credential) {
            $stands[$credential->setting()] = $credential->state();
        }

        return $stands;
    }

    /**
     * @param  list<string>  $credentials
     * @return list<string>
     */
    private static function refusedIn(WhatSetupDecided $decided, WhatWasFoundOfTheSettings $stored, array $credentials): array
    {
        $refused = $credentials;

        foreach ($decided as $decision) {
            $refused = $decision->credential ? [...$refused, $decision->key] : $refused;
        }

        foreach ($decided->settings() as $setting) {
            $refused = $setting->holds->either(shown: static fn(): AsText => AsText::nothing(), withheld: static fn(): AsText => AsText::of($setting->key))->said === '' ? $refused : [...$refused, $setting->key];
        }

        $secrets = $stored->either(
            found: static function (TheServicesSettings $read): ArrayObject {
                $named = [];

                foreach ($read->secrets() as $secret) {
                    $named[] = $secret->name();
                }

                return new ArrayObject($named);
            },
            met: static fn(): ArrayObject => new ArrayObject(),
        )->getArrayCopy();

        return array_values(array_unique(array_map(static fn(string $name): string => mb_strtoupper(trim($name)), [...$refused, ...$secrets])));
    }
}
