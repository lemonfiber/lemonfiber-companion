<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What an invitation writes on the account, flattened for the template.
 *
 * Every field is empty where the invitation wrote nothing about access, and
 * `wroteNothing` says so, so a template never draws *every library* for an
 * answer that named none because it set nothing at all.
 */
final readonly class WhatWasGrantedAsShown
{
    /**
     * @param bool         $wroteNothing   whether the invitation set nothing about what they may watch
     * @param list<string> $libraries      the libraries they may open, as named; empty with something written is every one
     * @param string       $limit          how far up the ratings they may watch, in the media server's words, or empty where no limit was set
     * @param string       $unratedSaid    the catalogue key for what becomes of unrated material, or empty
     * @param string       $requestingSaid the catalogue key for whether the request service was held to it, or empty
     * @param string       $filtering      what a limit is and is not, in the stack's words, or empty
     */
    public function __construct(
        public bool $wroteNothing,
        public array $libraries,
        public string $limit,
        public string $unratedSaid,
        public string $requestingSaid,
        public string $filtering,
    ) {}

    /** An invitation that wrote nothing about access. */
    public static function nothing(): self
    {
        return new self(wroteNothing: true, libraries: [], limit: '', unratedSaid: '', requestingSaid: '', filtering: '');
    }
}
