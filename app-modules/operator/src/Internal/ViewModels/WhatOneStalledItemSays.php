<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One thing whose download stopped, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\Stuck} hands its three facts over through a closure
 * and Blade has no way to call one, so
 * {@see \Modules\Operator\Internal\Presenters\HowAStalledItemReads} folds them
 * once per row into this — the argument {@see WhatOneRequestSays} makes, and
 * the reason that class exists.
 *
 * **All three fields are always set**, because the value they come from refuses
 * to be built without them. A row that reached a template with a title and no
 * service would be a line an operator cannot act on, and the type one layer
 * down exists to make that unspellable; this class must not undo it by
 * defaulting a field to the empty string.
 *
 * **The stage arrives as a key rather than as a word.** The word is the
 * catalogue's and the key is built from the case, so a stage added to the
 * contract cannot arrive here with a sentence written in this file that no
 * translator can reach (`L1`).
 *
 * `Internal` because it is a detail of how one surface reads a value; `E2`'s
 * promise is that anything here can be renamed without reading another module.
 */
final readonly class WhatOneStalledItemSays
{
    /**
     * @param string $title       what stopped, which is what the operator recognises
     * @param string $service     which service has it, so there is somewhere to go and look
     * @param string $stageSaid   the key for how far it got before it stopped
     * @param bool   $stillMoving whether anything is going to move it by itself
     */
    public function __construct(
        public string $title,
        public string $service,
        public string $stageSaid,
        public bool $stillMoving,
    ) {}
}
