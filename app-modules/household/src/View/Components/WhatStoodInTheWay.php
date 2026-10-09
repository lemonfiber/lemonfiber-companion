<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function is_string;
use function view;

/**
 * What a member met and what to do about it, as one notice's two lines.
 *
 * One component for every member screen, so the one decision is made once:
 * the core's own words are drawn as they were written, and only this app's
 * keys go through the catalogue. A sentence of the core's is never looked up,
 * so a line it writes that happens to read like a key is still drawn as
 * itself.
 */
final class WhatStoodInTheWay extends Component
{
    /** What the member met, as it is drawn. */
    public readonly string $said;

    /** What the member can do about it, as it is drawn, or empty where there is nothing to say. */
    public readonly string $todo;

    /**
     * @param string               $met               the core's sentence, or the catalogue key for what happened
     * @param string               $remedy            the core's remedy, or the catalogue key for what to do; empty for none
     * @param array<string, int>   $filling           what this app's lines are filled with
     * @param bool                 $inTheStacksWords  whether the two are the core's text rather than keys
     */
    public function __construct(Translator $catalogue, string $met, string $remedy = '', array $filling = [], bool $inTheStacksWords = false)
    {
        $this->said = $inTheStacksWords ? $met : $this->looked($catalogue, $met, $filling);
        $this->todo = $inTheStacksWords || $remedy === '' ? $remedy : $this->looked($catalogue, $remedy, $filling);
    }

    public function render(): View
    {
        return view('household::components.what-stood-in-the-way');
    }

    /**
     * One of this app's lines, filled.
     *
     * @param array<string, int> $filling
     */
    private function looked(Translator $catalogue, string $key, array $filling): string
    {
        $said = $catalogue->get($key, $filling);

        return is_string($said) ? $said : $key;
    }
}
