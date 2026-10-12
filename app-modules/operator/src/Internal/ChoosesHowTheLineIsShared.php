<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\WhatACapDoes;
use Modules\Operator\Internal\Presenters\HowTheLineChoicesRead;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Operator\Internal\ViewModels\TheChipsAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * What a change to the line asks for, as the operator fills it in before anything is sent.
 *
 * A trait rather than part of the screen, for the twenty-method ceiling: the
 * screen follows the change, and this is the half that decides what it asks.
 * Each field is empty where that part stays as it is.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ChoosesHowTheLineIsShared
{
    /** The download limit, as a share or a figure, bound to its field. */
    public string $down = '';

    /** The upload limit, bound to its field; empty leaves it to the stack's more careful default. */
    public string $up = '';

    /** The household's hours, bound to its field. */
    public string $active = '';

    /** What the line carries, bound to its field. */
    public string $line = '';

    /** The monthly cap, bound to its field. */
    public string $cap = '';

    /** What reaching the cap does, as one of {@see WhatACapDoes}' words, or empty to leave it. */
    public string $exceeded = '';

    /** How many minutes to lift the limits for, bound to its field; empty asks for no lift. */
    public string $lift = '';

    /** Say what reaching the cap does: `pause`, `throttle`, `continue`, or anything else to leave it. */
    public function exceededIs(string $word): void
    {
        $this->exceeded = WhatACapDoes::tryFrom($word)->value ?? '';
    }

    /** Lift the limits for one of the offered lengths, by its minutes, or for none where they name none. */
    public function liftFor(string $minutes): void
    {
        $length = HowLongALiftLasts::ofMinutes($minutes);
        $this->lift = $length instanceof HowLongALiftLasts ? (string) $length->minutes() : '';
    }

    /** What reaching the cap does as chosen here, and every way it can go. */
    public function exceededChoice(): TheChipsAsShown
    {
        return new HowTheLineChoicesRead()->exceeded(WhatACapDoes::tryFrom($this->exceeded));
    }

    /**
     * The lengths a lift is offered at, the one typed marked.
     *
     * @return list<AChoiceOfASettingAsShown>
     */
    public function liftChoice(): array
    {
        return new HowTheLineChoicesRead()->lift($this->lift);
    }
}
