<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function is_string;

use Modules\Household\Internal\Presenters\HowTheChannelsRead;
use Modules\Household\Internal\ViewModels\WhatOneChannelSays;

use function view;

/**
 * One channel on Home's Live TV shelf: a raised 16:9 tile with its number and name at the top, what is on now lettered across it, and until when beneath.
 *
 * **Read as one element**, for {@see Poster}'s reason: a screen reader hears
 * the channel, the programme and until when once.
 */
final class Channel extends Component
{
    /** What a screen reader says for the tile. */
    public readonly string $named;

    /** The line at the top of the tile: its number and its name. */
    public readonly string $above;

    /** What is on it now, or that the guide says nothing. */
    public readonly string $lettered;

    /** Until when, or nothing where the guide does not say. */
    public readonly string $below;

    public function __construct(Translator $catalogue, public readonly WhatOneChannelSays $channel)
    {
        $this->above = HowTheChannelsRead::shown($channel->number, $channel->name);
        $this->lettered = $channel->programme === '' ? $this->said($catalogue, 'household.live.nothing_in_the_guide', []) : $channel->programme;
        $this->below = $channel->until === '' ? $channel->certificate : $this->said($catalogue, 'household.live.until', ['time' => $channel->until]);
        $this->named = $channel->until === ''
            ? $this->said($catalogue, 'household.live.reads', ['channel' => $this->above, 'programme' => $this->lettered])
            : $this->said($catalogue, 'household.live.reads_until', ['channel' => $this->above, 'programme' => $this->lettered, 'time' => $channel->until]);
    }

    public function render(): View
    {
        return view('household::components.channel');
    }

    /** @param array<string, string> $filling */
    private function said(Translator $catalogue, string $key, array $filling): string
    {
        $said = $catalogue->get($key, $filling);

        return is_string($said) ? $said : $key;
    }
}
