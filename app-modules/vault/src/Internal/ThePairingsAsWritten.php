<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

use function array_is_list;
use function array_key_exists;

use InvalidArgumentException;

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\StacksBeingRemoved;

/**
 * The record of pairings as it is written into the store and read back out.
 *
 * One record for every pairing and for every stack whose removal has begun,
 * so the list a screen draws and the removals it leaves out are one read.
 */
final readonly class ThePairingsAsWritten
{
    /** The shape this build writes, and the only one it reads. */
    public const int SHAPE = 1;

    /** The record as it is written down, or false where it cannot be. */
    public static function written(ThePairings $pairings): string|false
    {
        return json_encode(KeptInAShape::written(self::SHAPE, self::shaped($pairings)));
    }

    /** What the store was holding; a part of it this build cannot read is none. */
    public static function read(string $written): ThePairings
    {
        $found = json_decode($written, associative: true);

        return new ThePairings(self::stacksIn($found), self::removingIn($found));
    }

    /** The stacks whose removal has begun, where the record says; a removal it cannot read is none. */
    private static function removingIn(mixed $found): StacksBeingRemoved
    {
        if (! KeptInAShape::isIn($found, self::SHAPE) || ! array_key_exists('removing', $found) || ! is_array($found['removing'])) {
            return StacksBeingRemoved::none();
        }

        $being = StacksBeingRemoved::none();

        foreach ($found['removing'] as $stored) {
            $being = self::alsoRemoving($being, $stored);
        }

        return $being;
    }

    /** These, and the stack a stored value names, where it names one. */
    private static function alsoRemoving(StacksBeingRemoved $being, mixed $stored): StacksBeingRemoved
    {
        if (! is_string($stored)) {
            return $being;
        }

        try {
            return $being->with(StackId::rememberedAs($stored));
        } catch (InvalidArgumentException) {
            return $being;
        }
    }

    /**
     * The record's fields as they go into the store, beside its shape.
     *
     * The address is taken with `forTheClient()` rather than by letting
     * `json_encode` reach `Address::jsonSerialize()`, which answers with a
     * placeholder on purpose. Writing it down has to be a deliberate
     * act in one visible place, and this is the place.
     *
     * @return array{stacks: list<array{id: string, name: string, address: string, fingerprint: string}>, removing: list<string>}
     */
    private static function shaped(ThePairings $pairings): array
    {
        $stacks = [];
        $removing = [];

        foreach ($pairings->stacks as $held) {
            $stacks[] = [
                'id' => $held->id()->stored(),
                'name' => $held->name()->shown(),
                'address' => $held->at()->forTheClient(),
                'fingerprint' => $held->presents()->forComparingByEye(),
            ];
        }

        foreach ($pairings->removing as $being) {
            $removing[] = $being->stored();
        }

        return ['stacks' => $stacks, 'removing' => $removing];
    }

    /**
     * What the store was holding, or nothing this app is willing to act on.
     *
     * Every refusal below answers `Configured::none()` rather than raising.
     * A launch is not a place to throw: the operator opened an app, and the
     * honest thing to show them is the screen for a device with no stack
     * configured, which is a screen that tells them what to do next.
     */
    private static function stacksIn(mixed $found): Configured
    {
        $rows = self::rowsIn($found);

        return $rows === null ? Configured::none() : self::rebuilt($rows);
    }

    /**
     * The stack rows a record holds, where it is a record this build wrote.
     *
     * Separated from reading them because the two decide different things: this
     * one is about the envelope — is this our shape, does it hold a list — and
     * {@see rebuilt()} is about what is inside. Together they were one method
     * with four ways out, which is the shape SonarCloud names `S1142` and is
     * right to: a reader counting the exits is a reader who has lost the thread.
     *
     * Answers the rows or nothing, rather than a `bool` beside a second read of
     * the same array. `C2` is about a published signature; this is private, and
     * a predicate here would narrow nothing for the analyser, so the caller
     * would re-check what this had just established.
     *
     * @return list<mixed>|null
     */
    private static function rowsIn(mixed $found): ?array
    {
        if (! KeptInAShape::isIn($found, self::SHAPE)) {
            return null;
        }

        if (! array_key_exists('stacks', $found)) {
            return null;
        }

        $rows = $found['stacks'];

        return is_array($rows) && array_is_list($rows) ? $rows : null;
    }

    /**
     * The rows as stacks, or nothing if any of them is not one.
     *
     * All or nothing, rather than skipping the rows that will not parse. A
     * half-read list is an app showing an operator some of their machines with
     * no sign that the rest are missing, and the missing one is the stack they
     * are looking for often enough to matter.
     *
     * The types refuse their own bad input — a blank name, a digest of the
     * wrong length, an address that is not one — and every one of those
     * refusals is a `Throwable` here rather than a condition to re-check. A
     * second copy of each rule in this method is a second place for them to
     * disagree.
     *
     * @param list<mixed> $rows
     */
    private static function rebuilt(array $rows): Configured
    {
        $stacks = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                return Configured::none();
            }

            $stack = self::stackFrom($row);

            if (! $stack instanceof Stack) {
                return Configured::none();
            }

            $stacks[] = $stack;
        }

        return Configured::of(...$stacks);
    }

    /**
     * One row as a stack, or nothing where it is not one.
     *
     * @param array<mixed> $row
     */
    private static function stackFrom(array $row): ?Stack
    {
        $id = self::text($row, 'id');
        $name = self::text($row, 'name');
        $address = self::text($row, 'address');
        $fingerprint = self::text($row, 'fingerprint');

        if ($id === null || $name === null || $address === null || $fingerprint === null) {
            return null;
        }

        // `InvalidArgumentException` rather than `Throwable`, which `C6` refuses
        // and is right to: every refusal this can legitimately meet is one of
        // the four value types saying the row is not one — a blank name, a
        // digest of the wrong length, an address with no scheme, an identifier
        // that is empty — and all four are that family. Anything outside it is
        // a bug in this method, and absorbing those is how a misspelled call
        // comes to be reported as a corrupt record.
        try {
            return Stack::of(
                StackId::rememberedAs($id),
                StackName::of($name),
                Address::of($address),
                Fingerprint::of($fingerprint),
            );
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * One named part of a row, where the row has it and it is text.
     *
     * Named rather than read with `??`, which `C9` refuses: `$row['id'] ?? null`
     * reads as a default and is really a suppressed notice, and the two are
     * indistinguishable at the call site.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, string $part): ?string
    {
        if (! array_key_exists($part, $row)) {
            return null;
        }

        $value = $row[$part];

        if (! is_string($value)) {
            return null;
        }

        return $value;
    }
}
