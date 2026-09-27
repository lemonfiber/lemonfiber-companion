<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One way of moving in, as the stack answered it: where it stands, and what it came to or would come to.
 *
 * **The stance is carried as given.** *Unchanged*, *pending* and *blocked* are
 * three answers that did nothing to the machine, and *applied* is the one
 * that did; {@see Stance} keeps all four apart, and nothing here turns one
 * into another.
 *
 * **A blocked move carries the stack's reason, and only a blocked one does.**
 * The two constructors are what hold that: {@see self::at()} refuses
 * *blocked*, and {@see self::blocked()} refuses a blank reason. A sentence
 * beside *applied* would be a reason a move did not happen drawn under the word
 * saying it did, which is {@see Stance}'s argument about a setting's review.
 */
final readonly class AMove
{
    private function __construct(
        private Stance $stance,
        private string $refusal,
        private TheAdoption|TheImport|TheStandingBeside|TheReplacement $came,
    ) {}

    /** The stack answered at this stance, which is any but blocked. */
    public static function at(Stance $stance, TheAdoption|TheImport|TheStandingBeside|TheReplacement $came): self
    {
        if ($stance === Stance::Blocked) {
            throw TheMoveSaysNothing::about('refusal');
        }

        return new self($stance, '', $came);
    }

    /** The stack turned it away, and this is why, in its words. */
    public static function blocked(string $because, TheAdoption|TheImport|TheStandingBeside|TheReplacement $came): self
    {
        if (trim($because) === '') {
            throw TheMoveSaysNothing::about('refusal');
        }

        return new self(Stance::Blocked, $because, $came);
    }

    /** Where it stands, as the stack said. */
    public function stance(): Stance
    {
        return $this->stance;
    }

    /** Why the stack turned it away, in its words, or `''` where it did not. */
    public function refusal(): string
    {
        return $this->refusal;
    }

    /** Which of the four ways of moving in this answers. */
    public function by(): MovingInBy
    {
        return match (true) {
            $this->came instanceof TheAdoption => MovingInBy::Adopting,
            $this->came instanceof TheImport => MovingInBy::Importing,
            $this->came instanceof TheStandingBeside => MovingInBy::StandingBeside,
            default => MovingInBy::Replacing,
        };
    }

    /**
     * Say what happens for each way of moving in, and get back what you built.
     *
     * @template TAdopting of object
     * @template TImporting of object
     * @template TBeside of object
     * @template TReplacing of object
     *
     * @param Closure(TheAdoption): TAdopting        $adopting
     * @param Closure(TheImport): TImporting         $importing
     * @param Closure(TheStandingBeside): TBeside    $standingBeside
     * @param Closure(TheReplacement): TReplacing    $replacing
     *
     * @return TAdopting|TImporting|TBeside|TReplacing
     */
    public function either(Closure $adopting, Closure $importing, Closure $standingBeside, Closure $replacing): object
    {
        return match (true) {
            $this->came instanceof TheAdoption => $adopting($this->came),
            $this->came instanceof TheImport => $importing($this->came),
            $this->came instanceof TheStandingBeside => $standingBeside($this->came),
            default => $replacing($this->came),
        };
    }
}
