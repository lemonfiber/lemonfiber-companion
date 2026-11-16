<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use InvalidArgumentException;

use function is_array;

use Modules\Kernel\Api\AnObstacleNamed;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Sentence;
use Modules\Sdk\Api\WireField;

/**
 * Each obstacle a read carries: its sentence alone for a member, and its code, sentence and remedy for the operator.
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

    /**
     * Every obstacle in a read's `findings`, whole, in the order sent.
     *
     * @param  array<mixed>          $table
     * @return list<AnObstacleNamed>
     */
    public static function namedIn(array $table, InvalidArgumentException $refused): array
    {
        $named = [];

        foreach (InTheOrderSent::rows(Required::rows($table, WireField::Findings, $refused)) as $obstacle) {
            $named[] = self::named($obstacle, $refused);
        }

        return $named;
    }

    /** The sentence one obstacle carries. */
    public static function of(mixed $obstacle, InvalidArgumentException $refused): Sentence
    {
        return is_array($obstacle) ? Sentence::of(Required::text($obstacle, WireField::Said, $refused)) : throw $refused;
    }

    /** One obstacle, whole. */
    public static function named(mixed $obstacle, InvalidArgumentException $refused): AnObstacleNamed
    {
        if (! is_array($obstacle)) {
            throw $refused;
        }

        return AnObstacleNamed::of(
            Code::of(Required::text($obstacle, WireField::Code, $refused)),
            self::of($obstacle, $refused),
            Remedy::of(Required::text($obstacle, WireField::Remedy, $refused)),
        );
    }
}
