<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ASettingMove;
use Modules\Kernel\Api\WhatASettingFallsBackTo;
use Modules\Kernel\Api\WhereADefaultComesFrom;
use Modules\Operator\Internal\ViewModels\ALineToSay;
use Modules\Operator\Internal\ViewModels\AServiceReadingItAsShown;
use Modules\Operator\Internal\ViewModels\ASettingMoveAsShown;

/**
 * One setting a change moves, with the default it falls back to as the stack said it at the move: never one read again since.
 */
final readonly class HowASettingMoveReads
{
    /** The move, as its lines. */
    public function moved(ASettingMove $move): ASettingMoveAsShown
    {
        $default = $move->fallsBackTo();
        $backToDefault = ! $move->value()->isHeld();

        return new ASettingMoveAsShown(
            moves: $backToDefault ? $this->backTo($move->setting(), $default) : $this->to($move),
            was: $move->was()->isHeld() ? new ALineToSay('plugins.settings.was', ['was' => $move->was()->said()]) : $this->wasAt($default),
            readers: $backToDefault && $default->from() === WhereADefaultComesFrom::EachReader ? $this->readers($default) : [],
            consequence: $move->consequence(),
        );
    }

    private function to(ASettingMove $move): ALineToSay
    {
        $filling = ['setting' => $move->setting(), 'value' => $move->value()->said()];

        return $move->fallsBackTo()->isTheSameAs($move->value())
            ? new ALineToSay('plugins.settings.moves_to_its_default', $filling)
            : new ALineToSay('plugins.settings.moves_to', $filling);
    }

    private function backTo(string $setting, WhatASettingFallsBackTo $default): ALineToSay
    {
        return match ($default->from()) {
            WhereADefaultComesFrom::TheCore, WhereADefaultComesFrom::Declared => $default->value()->said() === ''
                ? new ALineToSay('plugins.settings.moves_to_default_empty', ['setting' => $setting])
                : new ALineToSay('plugins.settings.moves_to_default', ['setting' => $setting, 'default' => $default->value()->said()]),
            WhereADefaultComesFrom::EachReader => new ALineToSay('plugins.settings.moves_to_each_readers_own', ['setting' => $setting]),
            WhereADefaultComesFrom::Nowhere => new ALineToSay('plugins.settings.moves_to_nothing', ['setting' => $setting]),
        };
    }

    private function wasAt(WhatASettingFallsBackTo $default): ALineToSay
    {
        return match ($default->from()) {
            WhereADefaultComesFrom::TheCore, WhereADefaultComesFrom::Declared => $default->value()->said() === ''
                ? new ALineToSay('plugins.settings.was_default_empty', [])
                : new ALineToSay('plugins.settings.was_default', ['default' => $default->value()->said()]),
            WhereADefaultComesFrom::EachReader => new ALineToSay('plugins.settings.was_each_readers_own', []),
            WhereADefaultComesFrom::Nowhere => new ALineToSay('plugins.settings.was_nothing', []),
        };
    }

    /** @return list<AServiceReadingItAsShown> */
    private function readers(WhatASettingFallsBackTo $default): array
    {
        $shown = [];

        foreach ($default->readers() as $reader) {
            $shown[] = AServiceReadingItAsShown::of($reader);
        }

        return $shown;
    }
}
