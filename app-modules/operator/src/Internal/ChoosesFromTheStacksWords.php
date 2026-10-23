<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function in_array;

use Modules\Operator\Internal\Presenters\HowTheQualityChoicesRead;
use Modules\Operator\Internal\ViewModels\AStacksWordOnAChip;
use Modules\Operator\Internal\ViewModels\TheQualityTurnedOutToBe;
use Native\Mobile\Edge\NativeComponent;

/**
 * What a quality choice asks for, as the operator taps it out of the stack's own words.
 *
 * A trait rather than part of the screen, for the twenty-method ceiling: the
 * screen sends the choice and follows what came of it, and this is the half
 * that decides what it asks.
 *
 * **Every chip is a word the stack offered.** A kind, a preset and a format
 * are each taken from what the last answer offered, and a word it did not
 * offer chooses nothing. Which kind takes a format is not decided here: a
 * preset or a format the kind does not take is the stack's to refuse, in its
 * words.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ChoosesFromTheStacksWords
{
    /**
     * The preset chosen, or the format for music; empty while none is.
     *
     * `public` because a NativeComponent keeps only its public properties
     * from one frame to the next.
     */
    public string $preset = '';

    /** The kind of media chosen, or empty for everything; `public` for the reason above. */
    public string $kind = '';

    /** What is in force, and with it what a choice may be made from. */
    abstract public function answer(): TheQualityTurnedOutToBe;

    /** Choose one of the offered presets or formats, or nothing where the word is neither. */
    public function presetIs(string $word): void
    {
        $offered = $this->answer();
        $this->preset = in_array($word, [...$offered->offeredPresets, ...$offered->offeredFormats], strict: true) ? $word : '';
    }

    /** Choose for one of the offered kinds of media, or for everything where the word is none of them. */
    public function kindIs(string $word): void
    {
        $this->kind = in_array($word, $this->answer()->offeredKinds, strict: true) ? $word : '';
    }

    /** Whether the choice is for everything rather than one kind. */
    public function choosesForEverything(): bool
    {
        return $this->kind === '';
    }

    /**
     * Every kind of media a choice may be set apart for, the one chosen marked.
     *
     * @return list<AStacksWordOnAChip>
     */
    public function kindsToChooseFrom(): array
    {
        return new HowTheQualityChoicesRead()->chips($this->answer()->offeredKinds, $this->kind);
    }

    /**
     * Every preset a choice may be made from, the one chosen marked.
     *
     * @return list<AStacksWordOnAChip>
     */
    public function presetsToChooseFrom(): array
    {
        return new HowTheQualityChoicesRead()->chips($this->answer()->offeredPresets, $this->preset);
    }

    /**
     * Every format music may take, the one chosen marked.
     *
     * @return list<AStacksWordOnAChip>
     */
    public function formatsToChooseFrom(): array
    {
        return new HowTheQualityChoicesRead()->chips($this->answer()->offeredFormats, $this->preset);
    }
}
