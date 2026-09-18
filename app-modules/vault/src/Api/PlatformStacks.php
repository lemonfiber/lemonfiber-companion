<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

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
use Modules\Kernel\Api\Remembered;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Native\Mobile\SecureStorage as Platform;
use Native\Mobile\SecureStorageStatus;

/**
 * The stacks this device is paired with, kept in the platform's own store.
 *
 * **The keychain rather than a file, and that is a decision rather than reuse.**
 * Secure storage is about sessions and does not reach this, so an ordinary file
 * would satisfy every requirement that names one. What it would not satisfy is
 * the one treating a stack's address as private: a file in the app's
 * sandbox is readable by a backup, by a device that has been rooted, and by
 * whatever a restore puts it back onto. The record here is an address, a
 * certificate digest and a name somebody chose for their house. The store that
 * already exists for the session is the conservative place to put it, and this
 * package offers no other.
 *
 * **One key for the whole record**, unlike {@see PlatformKeychain}'s key per
 * stack. They differ because sessions must be separable so that
 * one can be forgotten without touching another, and the configured list is a
 * single value that is read whole on every launch. A key per stack here would
 * mean enumerating a store that offers no enumeration.
 *
 * **A shape number, because everything retained carries one.** The version of
 * the shape a value was written in travels with it, and a shape this build does
 * not recognise is migrated or discarded — never interpreted as though it were
 * current. There is one shape so far, so there is nothing to migrate
 * and discarding is what happens: a record from a newer build of this app, or a
 * record that is not this record at all, reads as *no stacks configured* and
 * the operator lands on the screen for a device with no stacks.
 *
 * That is the conservative direction and it is worth being explicit about the
 * alternative. Reading an unrecognised record optimistically — taking the parts
 * that parse — would produce a stack list assembled from something nobody
 * wrote, which is an app offering to operate a machine it cannot name.
 */
final readonly class PlatformStacks implements Stacks
{
    /** The one key the whole record lives under. */
    private const string UNDER = 'lemonfiber.stacks';

    /** The shape this build writes, and the only one it reads. */
    private const int SHAPE = 1;

    /**
     * What the record holds once every pairing has been forgotten.
     *
     * The store keeps the key and writes an empty list into it, so a device
     * that has been unpaired answers `Found` with something in it. Named rather
     * than compared against inline: `D4` refuses a value checked against a
     * literal, and the reason applies here — this is the encoding's word for
     * empty, and it belongs beside the encoding.
     */
    private const string NOTHING_WRITTEN_DOWN = '[]';

    public function __construct(private Platform $store) {}

    public function configured(): Configured
    {
        $held = $this->store->read(self::UNDER);

        if ($held->status !== SecureStorageStatus::Found) {
            return Configured::none();
        }

        return $this->read($held->value ?? '');
    }

    /**
     * Whether the record exists and holds something, without reading what.
     *
     * The status and the emptiness of the value, and nothing parsed: a shut app
     * asking this has asked whether there is anything behind its lock, and
     * decoding the pairings to answer would be exactly the reading the lock sits
     * in front of.
     */
    public function holdsAny(): bool
    {
        $held = $this->store->read(self::UNDER);

        return $held->status === SecureStorageStatus::Found
            && $held->value !== null
            && $held->value !== ''
            && $held->value !== self::NOTHING_WRITTEN_DOWN;
    }

    public function remember(Stack $stack): Remembered
    {
        // Folded into what is already there rather than written on its own, so
        // that re-pairing replaces a machine instead of adding a second row —
        // the rule lives in `Configured::with()` and this does not restate it.
        $written = json_encode($this->shaped($this->configured()->with($stack)));

        if ($written === false || ! $this->store->set(self::UNDER, $written)) {
            return Remembered::refused($this->whyItRefused());
        }

        return Remembered::safely();
    }

    /**
     * The record as it goes into the store.
     *
     * The address is taken with `forTheClient()` rather than by letting
     * `json_encode` reach `Address::jsonSerialize()`, which answers with a
     * placeholder on purpose. Writing it down has to be a deliberate
     * act in one visible place, and this is the place.
     *
     * @return array{shape: int, stacks: list<array{id: string, name: string, address: string, fingerprint: string}>}
     */
    private function shaped(Configured $record): array
    {
        $stacks = [];

        foreach ($record as $held) {
            $stacks[] = [
                'id' => $held->id()->stored(),
                'name' => $held->name()->shown(),
                'address' => $held->at()->forTheClient(),
                'fingerprint' => $held->presents()->forComparingByEye(),
            ];
        }

        return ['shape' => self::SHAPE, 'stacks' => $stacks];
    }

    /**
     * What the store was holding, or nothing this app is willing to act on.
     *
     * Every refusal below answers `Configured::none()` rather than raising.
     * A launch is not a place to throw: the operator opened an app, and the
     * honest thing to show them is the screen for a device with no stack
     * configured, which is a screen that tells them what to do next.
     */
    private function read(string $written): Configured
    {
        $rows = $this->rowsIn(json_decode($written, associative: true));

        return $rows === null ? Configured::none() : $this->rebuilt($rows);
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
    private function rowsIn(mixed $found): ?array
    {
        if (! is_array($found) || ! array_key_exists('shape', $found) || $found['shape'] !== self::SHAPE) {
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
    private function rebuilt(array $rows): Configured
    {
        $stacks = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                return Configured::none();
            }

            $stack = $this->stackFrom($row);

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
    private function stackFrom(array $row): ?Stack
    {
        $id = $this->text($row, 'id');
        $name = $this->text($row, 'name');
        $address = $this->text($row, 'address');
        $fingerprint = $this->text($row, 'fingerprint');

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
    private function text(array $row, string $part): ?string
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

    /** Which of the two refusals this was, read from the store rather than guessed. */
    private function whyItRefused(): WhyAStackCannotBeRemembered
    {
        return $this->store->read(self::UNDER)->status === SecureStorageStatus::Unavailable
            ? WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage
            : WhyAStackCannotBeRemembered::StoreWouldNotOpen;
    }
}
