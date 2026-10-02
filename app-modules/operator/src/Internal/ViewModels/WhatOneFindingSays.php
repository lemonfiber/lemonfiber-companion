<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Remedies;
use Modules\Operator\Internal\WhatTheLogsAreOpenedWith;

/**
 * One row of a report, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\WhatTheCheckSaid::either()} answers with an object,
 * so that a caller cannot take a meaning out without saying what happens when a
 * check had nothing to say. That is the right shape for a value and the wrong
 * one for a Blade file, which has no `either()` and cannot be given one — so
 * {@see \Modules\Operator\Internal\Presenters\HowAFindingReads} answers it once
 * per row and the template reads fields.
 *
 * **A row with nothing wrong carries neither a meaning nor a remedy**, and the
 * empty strings are what the template branches on. A passing check has no
 * meaning to explain and no remedy to offer, and inventing a sentence for one
 * would be this app writing words the machine did not say.
 *
 * **A row with no answer carries a sentence and no code.** `unverified` and
 * `skipped` are checks that produced no verdict, so there is nothing to quote
 * and nothing to grade — but there is a reason, and for an unverified check
 * something to do about it. Those go in the same two fields a failure uses,
 * because an operator reading a row wants to know what it means and what to do
 * about it whichever outcome produced it. The empty code is the difference, and
 * it is a true one: there is no identifier to search for.
 *
 * **The detail the core added is last.** It has to be available and not
 * leading, and on a surface with one column that is a question about order: the
 * plain explanation and what to try come first, and this sits under them, so
 * whoever wants it has it and everybody else has already read the sentence
 * written for them.
 *
 * `Internal` because it is a detail of how this surface reads one value, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class WhatOneFindingSays
{
    /**
     * @param string   $title    what the check is called, in the core's words
     * @param string   $about    the key for which part of the machine it is about
     * @param string   $verdict  the key for the one word for where it stands: its cost where it has one, else its verdict
     * @param string   $code     the identifier an operator quotes, or empty
     * @param string   $meaning  what it means for them, or empty
     * @param string   $service  which service this is about, or empty
     * @param string   $called   what the stack calls that service, or empty where the report gave no name
     * @param string   $because  the title of what explains this, or empty
     * @param Remedies $remedies what to try, likeliest first, empty where none
     * @param string   $underneath the technical detail the core added, or empty
     * @param WhereARowSaysItCameFrom $from who put the check there
     * @param string   $exited   the code the service it is about exited on, where the stack said, or empty
     */
    public function __construct(
        public string $title,
        public string $about,
        public string $verdict,
        public string $code,
        public string $meaning,
        public string $service,
        public string $called,
        public string $because,
        public Remedies $remedies,
        public string $underneath,
        public WhereARowSaysItCameFrom $from,
        public string $exited = '',
    ) {}

    /**
     * The code, where it is said at the foot of the card.
     *
     * Only for a finding about the machine: one about a service carries its
     * code to that service's logs, which a finding about the machine has none
     * of, so its code stays here, under everything written for the operator.
     */
    public function codeAtTheFoot(): string
    {
        return $this->service === '' ? $this->code : '';
    }

    /**
     * What the road to the service's logs carries: the code, the code the
     * service exited on, and what the stack calls the service, where there is
     * each.
     *
     * @return array<string, string>
     */
    public function carriedToTheLogs(): array
    {
        return [
            ...WhatTheLogsAreOpenedWith::Reported->carrying($this->code),
            ...WhatTheLogsAreOpenedWith::Exited->carrying($this->exited),
            ...WhatTheLogsAreOpenedWith::Called->carrying($this->called),
        ];
    }

    /** Whether there is anything to explain, which is what the template asks. */
    public function explainsItself(): bool
    {
        return $this->meaning !== '';
    }

    /** A finding that says nothing to try is one that explains itself and names no remedy: the card says so. */
    public function saysNothingToTry(): bool
    {
        return $this->explainsItself() && $this->remedies->count() === 0;
    }
}
