<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * The four things a stack can say about who put something there — a setting's
 * value, a check, a service.
 *
 * The word, as a closed set. {@see WhoPutItThere} is the reading built
 * from it — the arm that carries a plugin's name, the arm that carries a
 * stack's reason, and the refusal of a blank one — and this is the vocabulary
 * both that and the wire are spelled in.
 *
 * **One enum for the boundary and the screen, not two.** The word arrives on
 * the wire, is converted here once with `tryFrom`, and the same case names the
 * catalogue line a template reads. A second copy for either end would be a
 * vocabulary with two spellings, which is the drift `D4` is about.
 *
 * **An enum rather than the union itself, because the two answer different
 * questions.** The union cannot be asked *what are all of you*: its arms are
 * static methods, so nothing can enumerate them, and a catalogue line missing
 * for one arm would be found by somebody reading `config.came_from_plugin` on
 * their own screen. A closed set can be enumerated, which is what lets
 * `EveryDerivedKeyResolvesTest` ask every case in every locale for a line.
 *
 * **The value is the stem**, which is `Permission::reason()`'s shape: a case
 * added here has a key by existing, so there is one spelling of each name and
 * no way for the two to drift.
 */
enum WhoSetIt: string
{
    /** The stack's own default, which nobody has changed. */
    case Bundled = 'bundled';

    /** Somebody set it, here or in the stack's own interfaces. */
    case Operator = 'operator';

    /** A plugin set it, and the row names which. */
    case Plugin = 'plugin';

    /** The stack could not work it out, and the row says what it said about that. */
    case Unknown = 'unknown';

    /** A plugin set it over a value that was there before. */
    case Overridden = 'overridden';

    /** A plugin set it and is no longer installed, and the value is still in force. */
    case Orphaned = 'orphaned';

    /**
     * The key for the sentence naming who set a setting.
     *
     * A key rather than the words: a class reaching for a translator it never
     * asked for has stopped telling the truth about what it needs, and the
     * template is where the words belong.
     *
     * **One key per surface, from one vocabulary.** The four words are the
     * core's and are the same everywhere; the sentence is not. *Set by the
     * plex plugin* is right of a value and wrong of a check, which the plugin
     * did not set but brought — so each surface that shows an origin has its
     * own line, and every line is derived from the same case.
     */
    public function ofASetting(): string
    {
        return sprintf('config.came_from_%s', $this->value);
    }

    /** The key for the sentence naming who put a check in the report. */
    public function ofACheck(): string
    {
        return sprintf('health.origin.%s', $this->value);
    }

    /** The key for the sentence naming who put a service on the stack. */
    public function ofAService(): string
    {
        return sprintf('stacks.outbound.theirs.origin.%s', $this->value);
    }
}
