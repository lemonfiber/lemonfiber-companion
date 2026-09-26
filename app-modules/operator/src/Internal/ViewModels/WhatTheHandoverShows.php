<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What came of handing a command over or taking it back, flattened for a template.
 *
 * {@see \Modules\Kernel\Api\HowTheHandoverWent} has three arms and hands its
 * answer through closures, which Blade cannot call, so
 * {@see \Modules\Operator\Internal\Presenters\HowAHandoverReads} folds it once.
 *
 * **Keys where a sentence is the catalogue's, words where they are the
 * stack's.** Every `…Said` field is a key a template translates; `$writesTo`,
 * `$touched` and `$refused` are what the stack said, shown as it said it.
 *
 * **An empty key is an arm not taken, and the template branches on it.** A
 * removal says nothing about starting or about where words are written; an act
 * that did not happen has no standing and no files. Printed unconditionally,
 * each would be a blank line where a sentence belongs.
 */
final readonly class WhatTheHandoverShows
{
    /**
     * @param string       $headingSaid        the key for what this is, taking `:name`
     * @param string       $name               lemonfiber's name for the command
     * @param bool         $rehearsed          whether the stack said this was a rehearsal
     * @param string       $startedSaid        the key for whether an install started it, or empty
     * @param string       $standingSaid       the key for where it stands now, or empty
     * @param string       $writesToSaid       the key for where its words go, taking `:output`, or empty
     * @param string       $writesTo           where its words go, as the stack said it, or empty
     * @param list<string> $touched            every file written or taken back, as the stack named them
     * @param string       $touchedSaid        the key each file is shown under, taking `:file`
     * @param string       $touchedNothingSaid the key for no file at all
     * @param string       $refused            the stack's own words for why it would not, or empty
     * @param string       $metSaid            the key for what the operator met instead, or empty
     */
    public function __construct(
        public string $headingSaid,
        public string $name,
        public bool $rehearsed,
        public string $startedSaid,
        public string $standingSaid,
        public string $writesToSaid,
        public string $writesTo,
        public array $touched,
        public string $touchedSaid,
        public string $touchedNothingSaid,
        public string $refused,
        public string $metSaid,
    ) {}
}
