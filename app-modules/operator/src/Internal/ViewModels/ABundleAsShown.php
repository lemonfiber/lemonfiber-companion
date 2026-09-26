<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A support bundle, flattened for a template.
 *
 * Everything the bundle holds is here, and nothing the app holds: no address,
 * no session, no word of this device's. What is drawn from this is what
 * whoever the operator hands the bundle to will read.
 */
final readonly class ABundleAsShown
{
    /**
     * @param int                $bytes        how large the file is, or would be
     * @param string             $whereSaid    the catalogue key for where it went, or would go
     * @param string             $where        the path on the stack's machine, blank where the stack named none
     * @param string             $window       how much of each service's logs it takes, in the stack's words
     * @param string             $filenamesSaid the catalogue key for whether media filenames are shown
     * @param list<string>       $revealed     the settings it shows as they are, by name
     * @param list<APieceAsShown> $pieces      every file it holds, in the stack's order
     * @param list<string>       $missing      what could not be collected, in the stack's words
     * @param string             $takenAt      when it was taken, as the stack wrote it
     * @param string             $lemonfiber   the version of lemonfiber that took it
     * @param string             $stack        the version of the stack it was taken from
     */
    public function __construct(
        public int $bytes,
        public string $whereSaid,
        public string $where,
        public string $window,
        public string $filenamesSaid,
        public array $revealed,
        public array $pieces,
        public array $missing,
        public string $takenAt,
        public string $lemonfiber,
        public string $stack,
    ) {}
}
