<?php

declare(strict_types=1);

namespace Tests\Support;

use function is_array;

use Modules\Kernel\Api\ACapabilityLeftContested;
use Modules\Kernel\Api\ACapabilityLeftUnfilled;
use Modules\Kernel\Api\AChangeAndWhy;
use Modules\Kernel\Api\AChangePutBack;
use Modules\Kernel\Api\AnAnswerOutOfContract;
use Modules\Kernel\Api\AnUpdate;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginChange;
use Modules\Kernel\Api\APluginInstall;
use Modules\Kernel\Api\APluginRemoval;
use Modules\Kernel\Api\AProof;
use Modules\Kernel\Api\ARecipe;
use Modules\Kernel\Api\ARecipeStep;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ASettingItOverrides;
use Modules\Kernel\Api\ASourceAsked;
use Modules\Kernel\Api\AValueItCarries;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\HowItsSourceStands;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheCapabilitiesLeftUnfilled;
use Modules\Kernel\Api\TheContestsLeft;
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\ThePluginChanges;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\ThePluginSources;
use Modules\Kernel\Api\TheProofs;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\TheRecipeSteps;
use Modules\Kernel\Api\TheSettingsItOverrides;
use Modules\Kernel\Api\TheValuesItCarries;
use Modules\Kernel\Api\TheVersionsItMovesBetween;
use Modules\Kernel\Api\WhatAChangePuts;
use Modules\Kernel\Api\WhatAProofCameTo;
use Modules\Kernel\Api\WhatGoingBackDoes;
use Modules\Kernel\Api\WhatPuttingTheOldVersionBackCameTo;
use Modules\Kernel\Api\WhatTheChecksMade;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhetherItWasRehearsed;

/**
 * One plugin, Tdarr, as a stack sends it and as this app holds it, in each of the states an install passes through.
 *
 * The two halves are written side by side so a contract test can hold the
 * adapter's reading of the wire against the fake's kernel values, and a
 * screen's test can stand on the same plugin.
 *
 * Every word is an ordinary one: nothing here is a secret.
 */
final readonly class APluginAsItArrives
{
    /** The reading's name. */
    public const string AGREEMENT = 'tdarr-2.1.0-1-change-1-pair';

    /** The one approval its recipe asks for. */
    public const string APPROVAL = 'library@hooks.example.com';

    /**
     * Tdarr, as a record on the wire holds it.
     *
     * @return array<string, mixed>
     */
    public static function onTheWire(): array
    {
        return [
            'plugin' => 'tdarr',
            'version' => '2.1.0',
            'name' => 'Tdarr',
            'services' => [],
            'from' => 'tdarr',
            'signed' => 'the catalogue key SHA256:abc',
            'declared' => ['reviewed' => true, 'upstream' => 'https://github.com/HaveAGitGat/Tdarr', 'license' => 'GPL-3.0'],
            'recipes' => [[
                'id' => 'link',
                'title' => 'Point Tdarr at the library',
                'why' => 'So it can transcode what arrives',
                'steps' => [
                    ['id' => 'ask', 'method' => 'POST', 'to' => 'sonarr', 'path' => '/api/v3/notification', 'adapter' => ['kind' => 'servarr', 'owner' => 'lemonfiber']],
                    ['id' => 'tell', 'method' => 'POST', 'to' => 'hooks.example.com', 'path' => '/hook', 'adapter' => null],
                ],
                'pairs' => [
                    ['value' => 'api_key', 'origin' => 'credential-store', 'to' => 'tdarr'],
                    ['value' => 'library', 'origin' => 'stack-service', 'to' => 'hooks.example.com', 'approval' => self::APPROVAL],
                ],
            ]],
        ];
    }

    /** Tdarr, as this app holds it. */
    public static function held(): APlugin
    {
        return APlugin::named(
            'tdarr',
            '2.1.0',
            'Tdarr',
            WhatVouchesForAPlugin::recorded('tdarr', '', 'the catalogue key SHA256:abc', reviewed: true, upstream: 'https://github.com/HaveAGitGat/Tdarr', licence: 'GPL-3.0'),
            TheRecipes::these(ARecipe::of(
                'link',
                'Point Tdarr at the library',
                'So it can transcode what arrives',
                TheRecipeSteps::these(
                    ARecipeStep::calling('ask', 'POST', 'sonarr', '/api/v3/notification', 'servarr'),
                    ARecipeStep::calling('tell', 'POST', 'hooks.example.com', '/hook', ''),
                ),
                TheValuesItCarries::these(
                    AValueItCarries::listed('api_key', 'credential-store', 'tdarr', '', '', ''),
                    AValueItCarries::listed('library', 'stack-service', 'hooks.example.com', self::APPROVAL, '', ''),
                ),
            )),
        );
    }

    /**
     * What the stack says of its plugins, with an install's account where one is given.
     *
     * @param  array<mixed>|null    $install
     * @return array<string, mixed>
     */
    public static function theAnswer(?array $install, string $agreement = ''): array
    {
        $data = [
            'installed' => [self::onTheWire()],
            'rehearsed' => false,
            'sources' => [['plugin' => 'tdarr', 'from' => 'tdarr', 'standing' => ['standing' => 'unreachable', 'why' => 'The catalogue did not answer']]],
            'nonconforming' => [['plugin' => 'tdarr', 'capability' => 'transcoding', 'operation' => 'queue', 'why' => 'It answered with a field its contract does not have', 'at' => '2026-10-09T21:00:00Z']],
        ];

        if ($install !== null) {
            $data['install'] = $install;
            $data['agreement'] = $agreement;
            $data['sources'] = [];
            $data['nonconforming'] = [];
        }

        return ['api_version' => 1, 'kind' => 'plugins', 'data' => $data];
    }

    /**
     * The account of installing Tdarr, before anything is written.
     *
     * @return array<string, mixed>
     */
    public static function aReadingOnTheWire(): array
    {
        return [
            'would' => self::onTheWire(),
            'recorded' => false,
            'changes' => [['path' => '/srv/lemonfiber/plugins/tdarr/plugin.toml', 'puts' => 'document']],
            'proofs' => [['proof' => 'answers', 'establishes' => 'It answers on its port', 'asks' => 'GET /api/status', 'why' => 'A service that does not answer is not running', 'of' => 'tdarr']],
            'contests' => [['capability' => 'transcode', 'by' => 'sonarr', 'claimants' => ['tdarr', 'unmanic']]],
            'overrides' => [['setting' => 'sonarr.rename', 'why' => 'Tdarr renames what it transcodes']],
            'recipes_ran' => [],
        ];
    }

    /**
     * The account after the yes, where its proof did not hold and the install went back.
     *
     * @return array<string, mixed>
     */
    public static function putBackOnTheWire(): array
    {
        $proofs = [['proof' => 'answers', 'establishes' => 'It answers on its port', 'asks' => 'GET /api/status', 'why' => 'A service that does not answer is not running', 'of' => 'tdarr', 'came_to' => ['outcome' => 'failed', 'faults' => ['It answered 502']]]];

        return [
            ...self::aReadingOnTheWire(),
            'proofs' => $proofs,
            'against' => 'service',
            'reversed' => [
                'rehearsed' => false,
                'reversed' => [['target' => 'tdarr', 'action' => ['does' => 'delete', 'path' => '/srv/lemonfiber/plugins/tdarr/plugin.toml']]],
                'left' => [['target' => 'tdarr-data', 'because' => 'The volume was in use']],
            ],
        ];
    }

    /**
     * The account after the yes, where everything held.
     *
     * @return array<string, mixed>
     */
    public static function installedOnTheWire(): array
    {
        return [
            ...self::aReadingOnTheWire(),
            'recorded' => true,
            'proofs' => [['proof' => 'answers', 'establishes' => 'It answers on its port', 'asks' => 'GET /api/status', 'why' => 'A service that does not answer is not running', 'of' => 'tdarr', 'came_to' => ['outcome' => 'passed']]],
            'against' => 'service',
            'verified' => ['broke' => [], 'unsettled' => []],
        ];
    }

    /**
     * What the stack says of its plugins, with an update's or a removal's account.
     *
     * @param  array<mixed>         $account
     * @return array<string, mixed>
     */
    public static function theAnswerAbout(string $which, array $account): array
    {
        $answer = self::theAnswer(null);
        $data = $answer['data'];
        $data = is_array($data) ? $data : [];
        $data[$which] = $account;
        $data['agreement'] = self::AGREEMENT;
        $data['sources'] = [];

        return [...$answer, 'data' => $data];
    }

    /**
     * What putting the installed version back would come to, or came to.
     *
     * @return array<string, mixed>
     */
    public static function wentBackOnTheWire(bool $rehearsed): array
    {
        return [
            'rehearsed' => $rehearsed,
            'reversed' => [['target' => 'tdarr', 'action' => ['does' => 'delete', 'path' => '/srv/lemonfiber/plugins/tdarr/plugin.toml']]],
            'left' => [['target' => '/srv/lemonfiber/config/tdarr', 'because' => 'What the service wrote stays for the new version']],
        ];
    }

    /**
     * Updating Tdarr from 2.1.0 to 2.2.0, before anything is written.
     *
     * @return array<string, mixed>
     */
    public static function anUpdateReadingOnTheWire(): array
    {
        return [
            'plugin' => 'tdarr',
            'from' => '2.1.0',
            'to' => '2.2.0',
            'interrupts' => ['tdarr'],
            'install' => self::aReadingOnTheWire(),
            'went_back' => self::wentBackOnTheWire(rehearsed: true),
        ];
    }

    /**
     * The update after the yes, where the new version did not hold and 2.1.0 went back.
     *
     * @return array<string, mixed>
     */
    public static function anUpdateNotHeldOnTheWire(): array
    {
        return [
            ...self::anUpdateReadingOnTheWire(),
            'install' => self::putBackOnTheWire(),
            'went_back' => self::wentBackOnTheWire(rehearsed: false),
            'stopped' => 'The container would not start',
            'restored' => ['version' => '2.1.0', 'placed' => true, 'running' => false],
        ];
    }

    /**
     * Removing Tdarr, before anything is taken.
     *
     * @return array<string, mixed>
     */
    public static function aRemovalReadingOnTheWire(): array
    {
        return [
            'plugin' => 'tdarr',
            'interrupts' => ['tdarr'],
            'leaves' => [['capability' => 'transcode', 'filled_by' => 'tdarr']],
            'removed' => false,
            'went_back' => self::wentBackOnTheWire(rehearsed: true),
        ];
    }

    /**
     * The removal after the yes, where the files went back and the record was not written.
     *
     * @return array<string, mixed>
     */
    public static function aPartialRemovalOnTheWire(): array
    {
        return [...self::aRemovalReadingOnTheWire(), 'went_back' => self::wentBackOnTheWire(rehearsed: false)];
    }

    /**
     * The removal after the yes: out of the record, with what the service wrote left on the machine.
     *
     * @return array<string, mixed>
     */
    public static function aRemovalOnTheWire(): array
    {
        return [...self::aRemovalReadingOnTheWire(), 'removed' => true, 'went_back' => self::wentBackOnTheWire(rehearsed: false)];
    }

    /** The update's reading, as this app holds it. */
    public static function theUpdateReading(): ThePlugins
    {
        return self::anUpdate(self::theReadingInstall(), self::wentBack(WhetherItWasRehearsed::Rehearsed), '', WhatPuttingTheOldVersionBackCameTo::notNeeded());
    }

    /** The update that did not hold, as this app holds it. */
    public static function theUpdateNotHeld(): ThePlugins
    {
        return self::anUpdate(
            self::thePutBackInstall(),
            self::wentBack(WhetherItWasRehearsed::CarriedOut),
            'The container would not start',
            WhatPuttingTheOldVersionBackCameTo::of('2.1.0', placed: true, running: false),
        );
    }

    /** The removal's reading, as this app holds it. */
    public static function theRemovalReading(): ThePlugins
    {
        return self::aRemoval(self::wentBack(WhetherItWasRehearsed::Rehearsed), removed: false);
    }

    /** The removal that went only part of the way, as this app holds it. */
    public static function thePartialRemoval(): ThePlugins
    {
        return self::aRemoval(self::wentBack(WhetherItWasRehearsed::CarriedOut), removed: false);
    }

    /** The removal, recorded, with what the service wrote left on the machine, as this app holds it. */
    public static function theRemoval(): ThePlugins
    {
        return self::aRemoval(self::wentBack(WhetherItWasRehearsed::CarriedOut), removed: true);
    }

    /** What is installed, as this app holds the listing. */
    public static function theListing(): ThePlugins
    {
        return ThePlugins::listed(
            TheInstalledPlugins::these(self::held()),
            ThePluginSources::these(ASourceAsked::of('tdarr', HowItsSourceStands::unreachable('The catalogue did not answer'))),
            TheAnswersOutOfContract::these(AnAnswerOutOfContract::of('tdarr', 'transcoding', 'queue', 'It answered with a field its contract does not have')),
        );
    }

    /** The reading, as this app holds it. */
    public static function theReading(): ThePlugins
    {
        return ThePlugins::aboutAnInstall(
            TheInstalledPlugins::these(self::held()),
            ThePluginSources::these(),
            self::AGREEMENT,
            self::theReadingInstall(),
        );
    }

    /** The install that went back, as this app holds it. */
    public static function thePutBack(): ThePlugins
    {
        return ThePlugins::aboutAnInstall(
            TheInstalledPlugins::these(self::held()),
            ThePluginSources::these(),
            self::AGREEMENT,
            self::thePutBackInstall(),
        );
    }

    /** The install that held, as this app holds it. */
    public static function theInstall(): ThePlugins
    {
        return ThePlugins::aboutAnInstall(
            TheInstalledPlugins::these(self::held()),
            ThePluginSources::these(),
            self::AGREEMENT,
            APluginInstall::reported(
                self::held(),
                recorded: true,
                changes: self::changes(),
                proofs: TheProofs::these(self::proof(WhatAProofCameTo::passed())),
                contests: self::contests(),
                overrides: self::overrides(),
                checks: WhatTheChecksMade::of(PluginLines::none(), PluginLines::none()),
            ),
        );
    }

    private static function anUpdate(APluginInstall $install, ARunPutBack $wentBack, string $stopped, WhatPuttingTheOldVersionBackCameTo $restored): ThePlugins
    {
        return ThePlugins::aboutAnUpdate(
            TheInstalledPlugins::these(self::held()),
            ThePluginSources::these(),
            self::AGREEMENT,
            AnUpdate::reported('tdarr', TheVersionsItMovesBetween::of('2.1.0', '2.2.0'), PluginLines::under('interrupts', 'tdarr'), $install, $wentBack, $stopped, $restored),
        );
    }

    private static function aRemoval(ARunPutBack $wentBack, bool $removed): ThePlugins
    {
        return ThePlugins::aboutAPluginRemoval(
            TheInstalledPlugins::these(self::held()),
            ThePluginSources::these(),
            self::AGREEMENT,
            APluginRemoval::reported(
                'tdarr',
                PluginLines::under('interrupts', 'tdarr'),
                TheCapabilitiesLeftUnfilled::these(ACapabilityLeftUnfilled::of('transcode', 'tdarr')),
                removed: $removed,
                wentBack: $wentBack,
            ),
        );
    }

    private static function wentBack(WhetherItWasRehearsed $rehearsed): ARunPutBack
    {
        return ARunPutBack::reported(
            $rehearsed,
            WhatWentBack::these(AChangePutBack::against('tdarr', WhatGoingBackDoes::Delete)),
            ChangesAndWhy::these(AChangeAndWhy::said('/srv/lemonfiber/config/tdarr', 'What the service wrote stays for the new version')),
            ChangesAndWhy::these(),
        );
    }

    private static function theReadingInstall(): APluginInstall
    {
        return APluginInstall::reported(
            self::held(),
            recorded: false,
            changes: self::changes(),
            proofs: TheProofs::these(self::proof(WhatAProofCameTo::notAsked())),
            contests: self::contests(),
            overrides: self::overrides(),
            checks: WhatTheChecksMade::notAsked(),
        );
    }

    private static function thePutBackInstall(): APluginInstall
    {
        return APluginInstall::putBack(
            self::held(),
            self::changes(),
            TheProofs::these(self::proof(WhatAProofCameTo::failed(PluginLines::under('faults', 'It answered 502')))),
            self::contests(),
            self::overrides(),
            WhatTheChecksMade::notAsked(),
            ARunPutBack::reported(
                WhetherItWasRehearsed::CarriedOut,
                WhatWentBack::these(AChangePutBack::against('tdarr', WhatGoingBackDoes::Delete)),
                ChangesAndWhy::these(AChangeAndWhy::said('tdarr-data', 'The volume was in use')),
                ChangesAndWhy::these(),
            ),
        );
    }

    private static function changes(): ThePluginChanges
    {
        return ThePluginChanges::these(APluginChange::at('/srv/lemonfiber/plugins/tdarr/plugin.toml', WhatAChangePuts::Document));
    }

    private static function proof(WhatAProofCameTo $cameTo): AProof
    {
        return AProof::of('answers', 'It answers on its port', 'GET /api/status', 'A service that does not answer is not running', $cameTo);
    }

    private static function contests(): TheContestsLeft
    {
        return TheContestsLeft::these(ACapabilityLeftContested::over('transcode', 'sonarr', PluginLines::under('claimants', 'tdarr', 'unmanic')));
    }

    private static function overrides(): TheSettingsItOverrides
    {
        return TheSettingsItOverrides::these(ASettingItOverrides::of('sonarr.rename', 'Tdarr renames what it transcodes'));
    }
}
