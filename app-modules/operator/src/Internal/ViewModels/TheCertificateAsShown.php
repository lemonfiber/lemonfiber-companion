<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * The certificate a stack presents, as its screen draws it: every text plain, exactly as the stack wrote it out.
 */
final readonly class TheCertificateAsShown
{
    /**
     * @param string       $atAGlance  its fingerprint in the comparable form
     * @param string       $pinned     the comparable form of the pin this phone holds for the stack
     * @param string       $comparing  the key for whether the two are the same
     * @param string       $subject    whom it was made out to
     * @param bool         $subjectCut whether the stack cut the subject short
     * @param string       $issuer     who made it out, empty where it signed itself
     * @param bool         $issuerCut  whether the stack cut the issuer short
     * @param list<string> $names      the names it covers, as many as the stack wrote out
     * @param int          $namesMore  how many more names it left out
     * @param AgoAsShown   $from       when it became valid, or will
     * @param string       $fromSaid   the key for the line saying so
     * @param AgoAsShown   $until      when it stops being valid, or stopped
     * @param string       $untilSaid  the key for the line saying so
     */
    public function __construct(
        public string $atAGlance,
        public string $pinned,
        public string $comparing,
        public string $subject,
        public bool $subjectCut,
        public string $issuer,
        public bool $issuerCut,
        public array $names,
        public int $namesMore,
        public AgoAsShown $from,
        public string $fromSaid,
        public AgoAsShown $until,
        public string $untilSaid,
    ) {}
}
