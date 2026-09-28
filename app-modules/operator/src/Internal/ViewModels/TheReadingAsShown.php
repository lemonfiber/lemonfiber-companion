<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What one removal would take off the machine, or took, as the rows that draw it.
 */
final readonly class TheReadingAsShown
{
    /**
     * @param string                        $tierSaid        the catalogue key for which removal this is
     * @param string                        $agreeSaid       the catalogue key for the yes to this removal
     * @param bool                          $takesTheLibrary whether it takes the library and the downloads
     * @param bool                          $endsThisSession whether it takes what admits this app to the stack
     * @param string                        $removes         what it takes, in the stack's words
     * @param string                        $keeps           what it leaves alone, in the stack's words
     * @param bool                          $isComplete      whether every source it needed answered
     * @param list<string>                  $unread          what could not be read, in the words of whatever refused
     * @param list<OneLineItReachesAsShown> $going           every line that goes
     * @param list<OneLineItReachesAsShown> $kept            every line kept, each with why
     * @param ASizeAsShown                  $frees           what the lines that go occupy, kept lines not counted
     * @param list<SomethingNotOursAsShown> $foreign         what beneath the data location is not lemonfiber's
     * @param list<SomethingComingAsShown>  $coming          what is still coming down
     * @param list<SomethingOutsideAsShown> $outside         what lemonfiber cannot take
     * @param string                        $volume          the stack's sentence about the volume the data is on, or empty
     * @param string                        $copyFirst       the stack's sentence about the copy it takes first, or empty
     */
    public function __construct(
        public string $tierSaid,
        public string $agreeSaid,
        public bool $takesTheLibrary,
        public bool $endsThisSession,
        public string $removes,
        public string $keeps,
        public bool $isComplete,
        public array $unread,
        public array $going,
        public array $kept,
        public ASizeAsShown $frees,
        public array $foreign,
        public array $coming,
        public array $outside,
        public string $volume,
        public string $copyFirst,
    ) {}
}
