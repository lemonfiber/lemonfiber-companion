<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ConfigEnvelope;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Sdk\Api\Fields\ConfigField;
use Modules\Sdk\Internal\Attributions;
use Modules\Sdk\Internal\Wire;

/**
 * The `config` envelope, read as everything the stack is set to.
 *
 * Written the way {@see Households} and {@see Offers} are — static, reading
 * through {@see WireField} so no field name is spelled twice, and refusing
 * rather than salvaging.
 *
 * **Two folds in one class, because they are two readings of one payload.**
 * `in()` is everything the stack is set to; `reviewIn()` is a proposed change
 * and where it stands. The same `config` envelope answers both, and which of
 * them is worth reading is decided by what was asked rather than by anything
 * on the wire — so this is a second reader over one payload rather than a
 * second payload, and {@see Offers} is written the same way for the same
 * reason.
 *
 * `findings` and `proof` are read by neither. What a change would disturb and
 * what proving a replacement credential came to are a third reading, for the
 * confirmation screen that does not exist yet.
 *
 * **`value` and `secret` go in together and come out as one thing.** The
 * contract's row is *one setting, as it is safe to show*: a withheld value is
 * already withheld on the other side, and `value` then carries the stack's own
 * note that it is set. Reading the two fields into {@see WhatASettingHolds}
 * here is what stops a boolean travelling any further into the app, where a
 * template would have to remember which way round it reads.
 */
final readonly class Dials
{
    /**
     * Everything this stack said it is set to, in the order it said it.
     *
     * @param Envelope<mixed> $envelope the `config` envelope
     */
    public static function in(Envelope $envelope): Settings
    {
        // No guard on `data` itself: the generated envelope types it, and a
        // check the analyser can already prove is one that reads as dead to
        // everything except the person adding it. What is not proven is the
        // shape underneath, which is what `listing()` refuses.
        $data = ConfigEnvelope::in(Wire::checked($envelope))->data;

        return Settings::of(...self::each(self::listing($data)));
    }

    /**
     * Where a proposed change stands, as the stack read it against what is in
     * force.
     *
     * A review is absent from every answer nobody proposed a change in, which
     * is every plain read of the listing. That is not a defect and not an
     * empty review — it is an answer to a question this call did not ask — so
     * it is refused here rather than returned hollow: a caller asking for a
     * review has proposed something, and an answer with none is one this app
     * could not read.
     *
     * @param Envelope<mixed> $envelope the `config` envelope, as a change answered it
     */
    public static function reviewIn(Envelope $envelope): WhereTheChangeStands
    {
        $data = ConfigEnvelope::in(Wire::checked($envelope))->data;
        $review = self::table($data, ConfigField::Review);
        $change = self::proposed(self::table($review, ConfigField::Change));
        $stance = self::stance($review);

        // Read off the stance rather than off the presence of a refusal. A
        // stack that sent a sentence beside `applied` has contradicted itself,
        // and taking the sentence would show an operator a reason a change did
        // not happen underneath the word saying it did.
        return $stance === Stance::Blocked
            ? WhereTheChangeStands::blocked($change, self::text($review, ConfigField::Refusal))
            : WhereTheChangeStands::at($change, $stance);
    }

    /**
     * The difference, as it would be applied.
     *
     * @param array<mixed> $change
     */
    private static function proposed(array $change): ProposedChange
    {
        return ProposedChange::of(
            self::text($change, ConfigField::Key),
            self::text($change, ConfigField::To),
            self::heldNow($change),
            self::cost($change),
        );
    }

    /**
     * What the setting holds before the change, where it holds anything.
     *
     * `from` absent and `from` null are the same answer — the setting holds
     * nothing yet — and both reach the same arm. A screen drawing an empty
     * line instead would say the setting is blank, which is a different thing
     * from nobody having set it.
     *
     * @param array<mixed> $change
     */
    private static function heldNow(array $change): WhatItHoldsNow
    {
        // Absent and null are spelled out rather than collapsed with `??`,
        // which would read as a default and is really an admission that nobody
        // knows whether the key is there. Here both genuinely mean the same
        // thing and the contract says so — `from` is optional and nullable,
        // both for *holds nothing yet* — so this is the one place the two are
        // deliberately one arm, and it says which two they are.
        if (! array_key_exists(WireField::From->value, $change)) {
            return WhatItHoldsNow::nothingYet();
        }

        $held = $change[WireField::From->value];

        if ($held === null) {
            return WhatItHoldsNow::nothingYet();
        }

        if (! is_string($held)) {
            throw SettingIsUnreadable::missing(WireField::From);
        }

        return WhatItHoldsNow::shown($held);
    }

    /**
     * One field as a table, refused unless it is one.
     *
     * @param array<mixed> $held
     * @return array<mixed>
     */
    private static function table(array $held, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $held)) {
            throw SettingIsUnreadable::missing($field);
        }

        $under = $held[$field->value];

        if (! is_array($under)) {
            throw SettingIsUnreadable::missing($field);
        }

        return $under;
    }

    /**
     * Where the change stands, as one of the four words the contract has.
     *
     * Spelled out rather than shared with {@see Cost()} through a class name
     * in a variable. One generic reader would be shorter and the module
     * boundary rules could not see through it — they work by reading the
     * names a file writes down, and a member reached through a variable is a
     * name nothing here wrote.
     *
     * A word outside the set is refused rather than mapped to a default. It
     * is an answer from a lemonfiber this app cannot read, and telling an
     * operator so is truer than guessing which stance was meant — a guess of
     * `applied` would be the worst of the four to be wrong about.
     *
     * @param array<mixed> $review
     */
    private static function stance(array $review): Stance
    {
        $said = Stance::tryFrom(self::text($review, ConfigField::Stance));

        if ($said === null) {
            throw SettingIsUnreadable::missing(ConfigField::Stance);
        }

        return $said;
    }

    /**
     * What applying it costs, as one of the two words the contract has.
     *
     * Refused rather than defaulted, and this is the one where a default
     * would be dangerous in a particular direction: reading an unknown word
     * as `cheap` would apply a consequential change without asking anybody.
     *
     * @param array<mixed> $change
     */
    private static function cost(array $change): Cost
    {
        $said = Cost::tryFrom(self::text($change, WireField::Cost));

        if ($said === null) {
            throw SettingIsUnreadable::missing(WireField::Cost);
        }

        return $said;
    }

    /**
     * The `settings` list, refused unless it is one.
     *
     * An absent `settings` is refused rather than read as an empty listing.
     * They draw identically — a screen saying nothing is set — and the two are
     * opposite: one is a stack with nothing set, the other is an answer this
     * app could not read. A stack that has nothing set says so with an empty
     * list, which this accepts.
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private static function listing(array $data): array
    {
        if (! array_key_exists(ConfigField::Settings->value, $data)) {
            throw SettingIsUnreadable::missing(ConfigField::Settings);
        }

        $listed = $data[ConfigField::Settings->value];

        if (! is_array($listed)) {
            throw SettingIsUnreadable::notAList();
        }

        return $listed;
    }

    /**
     * Every row of the listing, in order.
     *
     * @param array<mixed> $listed
     * @return list<Setting>
     */
    private static function each(array $listed): array
    {
        $set = [];
        $position = 0;

        foreach ($listed as $row) {
            if (! is_array($row)) {
                throw SettingIsUnreadable::row($position);
            }

            $set[] = self::one($row);
            $position++;
        }

        return $set;
    }

    /**
     * One row: its name, and what it holds.
     *
     * @param array<mixed> $row
     */
    private static function one(array $row): Setting
    {
        return Setting::called(
            self::text($row, ConfigField::Key),
            self::holds($row),
            self::from($row),
        );
    }

    /**
     * Who put this value here.
     *
     * Read by {@see Attributions}, which every envelope carrying an origin
     * shares, and refused with the listing where it cannot be read — the way
     * an unreadable `secret` is. The screen's promise is that it shows what
     * the stack is set to and who set it; a listing that quietly loses the
     * second half is the silent subset this reader exists to refuse.
     *
     * @param array<mixed> $row
     */
    private static function from(array $row): WhoPutItThere
    {
        try {
            return Attributions::of($row);
        } catch (OriginIsUnreadable $why) {
            throw SettingIsUnreadable::origin($why);
        }
    }

    /**
     * Which of the two things this row's value is.
     *
     * The flag is read here and goes no further. Everything after this point
     * has a value it may print or a note it may print, and no way to confuse
     * one for the other.
     *
     * @param array<mixed> $row
     */
    private static function holds(array $row): WhatASettingHolds
    {
        $said = self::text($row, WireField::Value);

        if (! array_key_exists(WireField::Secret->value, $row)) {
            throw SettingIsUnreadable::missing(WireField::Secret);
        }

        $withheld = $row[WireField::Secret->value];

        if (! is_bool($withheld)) {
            // Refused rather than read for truthiness. A `"false"` arriving as
            // a string is true to PHP, and the reading that goes wrong is the
            // one that prints a withheld note as a value.
            throw SettingIsUnreadable::missing(WireField::Secret);
        }

        return $withheld
            ? WhatASettingHolds::withheld($said)
            : WhatASettingHolds::shown($said);
    }

    /**
     * One field of a row, as text.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $row)) {
            throw SettingIsUnreadable::missing($field);
        }

        $said = $row[$field->value];

        if (! is_string($said)) {
            throw SettingIsUnreadable::missing($field);
        }

        return $said;
    }
}
