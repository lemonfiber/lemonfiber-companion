<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One thing whose download stopped, and the three facts that make it actionable.
 *
 * `N2-R9` asks for stuck downloads to be reachable, and *reachable* is only
 * worth anything if what is reached can be acted on. The contract sends
 * `{service, stage, title}` for each one, and those three are one fact in three
 * parts: **what** stopped, **where** it stopped, and **which service** has it.
 *
 * Any two of them strand the operator. Title and stage with no service is a
 * problem with nowhere to go and look. Title and service with no stage sends
 * somebody to the download client for a title the indexer never found a release
 * for. Stage and service with no title is a sentence about nothing.
 *
 * So there is no `title()`. {@see self::stated()} hands over all three together
 * for {@see Repair::stated()}'s reason — the failure being designed out is a
 * template built around the one field that reads like a label, with the other
 * two sitting in the envelope and never reaching the screen. That is invisible
 * in review, because the screen looks finished.
 */
final readonly class Stuck
{
    private function __construct(
        private string $title,
        private string $service,
        private Stage $stage,
    ) {}

    /**
     * The one place a stalled item the stack listed becomes one this app shows.
     *
     * A blank title is refused because it renders as an empty row somebody is
     * asked to act on, and a blank service because it is a row with nowhere to
     * go — {@see StuckSaysNothing} says which.
     */
    public static function at(string $title, string $service, Stage $stage): self
    {
        $named = trim($title);

        if ($named === '') {
            throw StuckSaysNothing::itIsCalled();
        }

        $owner = trim($service);

        if ($owner === '') {
            throw StuckSaysNothing::whichServiceHasIt();
        }

        return new self($named, $owner, $stage);
    }

    /**
     * Whether anything is still going to happen to this by itself.
     *
     * Published on its own where the three facts are not, and the asymmetry is
     * {@see Repair::answers()}'s: this is not something the operator reads, it
     * is how a screen decides which rows go under which heading. A screen
     * holding it has learned nothing about what stopped.
     */
    public function stillMoving(): bool
    {
        return $this->stage->stillMoving();
    }

    /**
     * Say all three, and get whatever saying them produced.
     *
     * One closure rather than three accessors, because the three are one
     * requirement and three getters are three chances to call two of them.
     *
     * @template TSaid of object
     *
     * @param Closure(string, string, Stage): TSaid $say
     *
     * @return TSaid
     */
    public function stated(Closure $say): object
    {
        return $say($this->title, $this->service, $this->stage);
    }
}
