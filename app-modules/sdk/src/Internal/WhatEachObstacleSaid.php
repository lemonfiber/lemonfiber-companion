<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use InvalidArgumentException;

use function is_array;

use Modules\Kernel\Api\Sentence;
use Modules\Sdk\Api\WireField;

/**
 * The sentence each obstacle a read carries says, as the core wrote it.
 */
final readonly class WhatEachObstacleSaid
{
    /**
     * The sentence of every obstacle in a read's `findings`, in the order sent.
     *
     * @param  array<mixed>   $table
     * @return list<Sentence>
     */
    public static function in(array $table, InvalidArgumentException $refused): array
    {
        $said = [];

        foreach (InTheOrderSent::rows(Required::rows($table, WireField::Findings, $refused)) as $obstacle) {
            $said[] = self::of($obstacle, $refused);
        }

        return $said;
    }

    /** The sentence one obstacle carries. */
    public static function of(mixed $obstacle, InvalidArgumentException $refused): Sentence
    {
        return is_array($obstacle) ? Sentence::of(Required::text($obstacle, WireField::Said, $refused)) : throw $refused;
    }
}
