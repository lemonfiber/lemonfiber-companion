<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\ACapabilityLeftContested;
use Modules\Kernel\Api\AChangeAndWhy;
use Modules\Kernel\Api\AChangePutBack;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginChange;
use Modules\Kernel\Api\APluginInstall;
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
use Modules\Kernel\Api\WhatAChangePuts;
use Modules\Kernel\Api\WhatAProofCameTo;
use Modules\Kernel\Api\WhatGoingBackDoes;
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
        ];

        if ($install !== null) {
            $data['install'] = $install;
            $data['agreement'] = $agreement;
            $data['sources'] = [];
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

    /** What is installed, as this app holds the listing. */
    public static function theListing(): ThePlugins
    {
        return ThePlugins::listed(
            TheInstalledPlugins::these(self::held()),
            ThePluginSources::these(ASourceAsked::of('tdarr', HowItsSourceStands::unreachable('The catalogue did not answer'))),
        );
    }

    /** The reading, as this app holds it. */
    public static function theReading(): ThePlugins
    {
        return ThePlugins::aboutAnInstall(
            TheInstalledPlugins::these(self::held()),
            ThePluginSources::these(),
            self::AGREEMENT,
            APluginInstall::reported(
                self::held(),
                recorded: false,
                changes: self::changes(),
                proofs: TheProofs::these(self::proof(WhatAProofCameTo::notAsked())),
                contests: self::contests(),
                overrides: self::overrides(),
                checks: WhatTheChecksMade::notAsked(),
            ),
        );
    }

    /** The install that went back, as this app holds it. */
    public static function thePutBack(): ThePlugins
    {
        return ThePlugins::aboutAnInstall(
            TheInstalledPlugins::these(self::held()),
            ThePluginSources::these(),
            self::AGREEMENT,
            APluginInstall::putBack(
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
            ),
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
