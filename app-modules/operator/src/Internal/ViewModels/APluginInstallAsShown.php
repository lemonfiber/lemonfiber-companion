<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * An install's account, as the template draws it: a reading before the yes, and what it came to after.
 *
 * The same lists either way. A reading is labelled as one and every proof on
 * it is *not asked*; after the yes the headline is *installed* only where the
 * record was written, no proof failed and the stack's checks found nothing
 * broken.
 */
final readonly class APluginInstallAsShown
{
    /**
     * @param APluginAsShown              $plugin     the plugin, as the install settles it
     * @param bool                        $isAReading whether nothing was written, so this is what it would do
     * @param bool                        $agreeable  whether it is a reading with a name a yes can quote, so Install and the switches are offered
     * @param string                      $headline   the catalogue key for how it ended, or empty on a reading
     * @param list<APluginChangeAsShown>  $changes    every change, in order
     * @param list<AProofAsShown>         $proofs     every proof, with what asking it came to
     * @param list<AChangeAndWhyAsShown>  $overrides  every bundled setting it changes, with why
     * @param list<AContestAsShown>       $contests   every ask it would leave contested
     * @param bool                        $checked    whether the stack's own checks were asked
     * @param list<string>                $broke      every check it made worse, by title
     * @param list<string>                $unsettled  every check nothing could be concluded about, by title
     * @param HowPuttingARunBackWent|null $putBack    what putting it back came to, where it was
     */
    public function __construct(
        public APluginAsShown $plugin,
        public bool $isAReading,
        public bool $agreeable,
        public string $headline,
        public array $changes,
        public array $proofs,
        public array $overrides,
        public array $contests,
        public bool $checked,
        public array $broke,
        public array $unsettled,
        public ?HowPuttingARunBackWent $putBack,
    ) {}
}
