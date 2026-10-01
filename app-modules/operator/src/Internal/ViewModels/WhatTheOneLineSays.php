<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * The health summary as a template draws it, including when there is none to draw.
 *
 * Every state carries every field, and a field with nothing to say is empty
 * rather than invented: a screen that has heard nothing yet has no count, no
 * worst thing, no affected items and nothing stopped, and says that it is
 * waiting.
 *
 * **Slow is its own list.** It is on the stack's list of what stopped moving
 * because it has been watched, and it needs time rather than a fix, so it is
 * never drawn under the heading that says something is stuck.
 */
final readonly class WhatTheOneLineSays
{
    /**
     * @param string                      $said      the key for the one line
     * @param string                      $word      the key for the same line as a single word
     * @param string                      $tone      the tone its glyph is drawn in, a `Modules\Design\View\Tone` value
     * @param string                      $counted   the key for how many things it counts, or empty where it counts none
     * @param int                         $howMany   how many things want attention, as the core counted them
     * @param string                      $worst     the worst thing, named, or empty where the core named nothing
     * @param AgoAsShown                  $ago       when the summary shown was heard, or live where it is current
     * @param string                      $met       the key for what stopped the subscription, or empty
     * @param string                      $remedy    the key for what to do about that, or empty
     * @param array<string, int>          $filling   what those two sentences are filled with, or nothing
     * @param list<AnAffectedItemAsShown> $affected  every thing counted as wrong, worst first
     * @param list<AStoppageAsShown>      $stopped   what stopped in the queue and wants a fix, in the stack's order
     * @param list<AStoppageAsShown>      $slow      what is slow and still moving, in the stack's order
     */
    public function __construct(
        public string $said,
        public string $word,
        public string $tone,
        public string $counted,
        public int $howMany,
        public string $worst,
        public AgoAsShown $ago,
        public string $met,
        public string $remedy,
        public array $filling,
        public array $affected,
        public array $stopped,
        public array $slow,
    ) {}
}
