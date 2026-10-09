<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The version an update moves a plugin from, and the one it moves it to.
 *
 * One value because neither means anything without the other: they are what
 * the operator agrees to, said as one line before any service stops.
 */
final readonly class TheVersionsItMovesBetween
{
    private function __construct(private string $from, private string $to) {}

    /** The two versions, as the stack named them; either blank is refused. */
    public static function of(string $from, string $to): self
    {
        foreach (['from' => $from, 'to' => $to] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($from, $to);
    }

    /** The version it moves from, the one installed now. */
    public function from(): string
    {
        return $this->from;
    }

    /** The version it moves to, the one its source serves now. */
    public function to(): string
    {
        return $this->to;
    }
}
