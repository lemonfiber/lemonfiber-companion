<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\ConnectionsReverted;
use Modules\Kernel\Api\TheReset;
use Modules\Kernel\Api\TheStackEdits;

/**
 * One reset, as the kernel holds it and as a stack sends it.
 *
 * The two halves are written together so a contract test comparing the fake
 * with the adapter compares like with like. The diff is the stack's own
 * shape, each line marked as the operator's or lemonfiber's, which a payload
 * built from the declaration alone cannot be: the contract types it as text.
 *
 * One file has a line each way and one differs in no line that can be shown,
 * so a reader that dropped an empty diff would be caught.
 */
final readonly class WhatAResetSays
{
    /** The diff of the one file whose lines differ. */
    private const string THE_DIFF = "- image: sonarr:4.0.1\n+ image: sonarr:4.0.0\n";

    /**
     * The `reset` envelope a finished job answers with, changed where a case says.
     *
     * @param  array<mixed>         $changed the report's own fields that differ
     * @return array<string, mixed>
     */
    public static function envelope(bool $confirmed, array $changed = []): array
    {
        return ['api_version' => 1, 'kind' => 'reset', 'data' => [
            'confirmed' => $confirmed,
            'rehearsed' => false,
            'reverted' => [
                ['path' => 'compose.yaml', 'diff' => self::THE_DIFF],
                ['path' => 'env/sonarr.env', 'diff' => ''],
            ],
            'reverted_connections' => ['sonarr → qbittorrent'],
            ...$changed,
        ]];
    }

    /** What that envelope previews, as the kernel holds it. */
    public static function previewed(): TheReset
    {
        return TheReset::previewed(self::edits(), ConnectionsReverted::these('sonarr → qbittorrent'));
    }

    /** What that envelope reports carried out, as the kernel holds it. */
    public static function carriedOut(): TheReset
    {
        return TheReset::carriedOut(self::edits(), ConnectionsReverted::these('sonarr → qbittorrent'));
    }

    /** The files it reverts. */
    private static function edits(): TheStackEdits
    {
        return TheStackEdits::these(
            AStackEdit::at('compose.yaml', self::THE_DIFF),
            AStackEdit::at('env/sonarr.env', ''),
        );
    }
}
