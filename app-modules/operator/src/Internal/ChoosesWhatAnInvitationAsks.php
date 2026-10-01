<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Operator\Internal\Presenters\HowTheUnratedChoiceReads;
use Modules\Operator\Internal\ViewModels\TheUnratedChoiceAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * What an invitation asks for, as the operator fills it in before anything is sent.
 *
 * The name, the libraries, the age limit and what becomes of unrated material.
 * A trait rather than part of the screen, for the twenty-method ceiling: the
 * screen follows the invitation, and this is the half that decides what it
 * asks for.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ChoosesWhatAnInvitationAsks
{
    /**
     * The name they will sign in as, bound to its field.
     *
     * Public for {@see Screens\WhatThisStackIsSetTo::$typed}'s reason, which is
     * also why the template reaches it: the package fills a view from a
     * screen's public properties.
     */
    public string $name = '';

    /** The libraries they may open, separated by commas, bound to its field; empty is every one. */
    public string $libraries = '';

    /** The age above which things are held back, bound to its field; empty is no limit. */
    public string $age = '';

    /** What becomes of unrated material, as one of {@see WhatBecomesOfUnrated}'s words, or empty to leave it to the stack. */
    public string $unrated = '';

    /** Say what becomes of unrated material: `held-back`, `let-through`, or anything else to leave it to the stack. */
    public function unratedIs(string $word): void
    {
        $this->unrated = WhatBecomesOfUnrated::tryFrom($word)->value ?? '';
    }

    /** What becomes of unrated material as chosen here, and every way it can go. */
    public function unratedChoice(): TheUnratedChoiceAsShown
    {
        return new HowTheUnratedChoiceReads()->chosen(WhatBecomesOfUnrated::tryFrom($this->unrated));
    }
}
