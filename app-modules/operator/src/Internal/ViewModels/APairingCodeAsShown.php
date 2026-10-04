<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A pairing code, as the fields a screen draws.
 *
 * Every word is the stack's; the squares are its line, drawn.
 */
final readonly class APairingCodeAsShown
{
    /**
     * @param list<list<bool>> $squares   the line as a code, rows of squares dark where true, or none where it could not be drawn
     * @param string           $line      the line another phone types, as the stack wrote it
     * @param string           $compare   the short form of the fingerprint the other phone shows once it is typed
     * @param string           $until     when it stops being good, as this phone's clock reads it
     * @param string           $address   where the other phone reaches this machine
     * @param string           $caution   what is worth knowing about that address, or empty
     * @param string           $replacing what replacing the certificate would cost every paired phone
     */
    public function __construct(
        public array $squares,
        public string $line,
        public string $compare,
        public string $until,
        public string $address,
        public string $caution,
        public string $replacing,
    ) {}
}
