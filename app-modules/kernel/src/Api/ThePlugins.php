<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a stack says of its plugins: which are installed, how each one's source stands, what their adapters answered outside their contracts, and the install, update, removal, proof or recipe run it was asked about.
 *
 * **The listing is what the record holds.** After a reading, or an act that
 * was put back, it is what it held before: something nobody recorded is
 * never counted.
 *
 * **The agreement is the reading's name**, and only a reading has one. It is
 * what a yes quotes, so the stack carries out what was shown or refuses a
 * reading that has moved on. An answer is about one act at most: an install,
 * an update, a removal, a proof and a recipe run are five accounts, never one.
 */
final readonly class ThePlugins
{
    private function __construct(
        private TheInstalledPlugins $installed,
        private ThePluginSources $sources,
        private string $agreement,
        private APluginInstall|AnUpdate|APluginRemoval|APluginReproof|ARecipeRun|null $about,
        private TheAnswersOutOfContract $outOfContract,
    ) {}

    /** The listing alone, with the answers its plugins gave outside their contracts. */
    public static function listed(TheInstalledPlugins $installed, ThePluginSources $sources, TheAnswersOutOfContract $outOfContract): self
    {
        return new self($installed, $sources, '', null, $outOfContract);
    }

    /** An answer about an install, beside what the record holds; `agreement` is empty where the stack named none. */
    public static function aboutAnInstall(TheInstalledPlugins $installed, ThePluginSources $sources, string $agreement, APluginInstall $install): self
    {
        return new self($installed, $sources, trim($agreement), $install, TheAnswersOutOfContract::these());
    }

    /** An answer about an update, beside what the record holds. */
    public static function aboutAnUpdate(TheInstalledPlugins $installed, ThePluginSources $sources, string $agreement, AnUpdate $update): self
    {
        return new self($installed, $sources, trim($agreement), $update, TheAnswersOutOfContract::these());
    }

    /** An answer about a removal, beside what the record holds. */
    public static function aboutAPluginRemoval(TheInstalledPlugins $installed, ThePluginSources $sources, string $agreement, APluginRemoval $removal): self
    {
        return new self($installed, $sources, trim($agreement), $removal, TheAnswersOutOfContract::these());
    }

    /** An answer about proving a plugin again, beside what the record holds. */
    public static function aboutAReproof(TheInstalledPlugins $installed, ThePluginSources $sources, string $agreement, APluginReproof $proof): self
    {
        return new self($installed, $sources, trim($agreement), $proof, TheAnswersOutOfContract::these());
    }

    /** An answer about running one of a plugin's demand recipes, beside what the record holds. */
    public static function aboutARecipeRun(TheInstalledPlugins $installed, ThePluginSources $sources, string $agreement, ARecipeRun $run): self
    {
        return new self($installed, $sources, trim($agreement), $run, TheAnswersOutOfContract::these());
    }

    /** The answers that plugin's adapters gave outside their contracts, which it fills nothing for until it is proved again. */
    public function answersOutOfContractOf(APlugin $plugin): TheAnswersOutOfContract
    {
        return $this->outOfContract->of($plugin);
    }

    /** Every plugin the record holds. */
    public function installed(): TheInstalledPlugins
    {
        return $this->installed;
    }

    /** How this plugin's source stands. */
    public function sourceOf(APlugin $plugin): HowItsSourceStands
    {
        return $this->sources->of($plugin);
    }

    /** The reading's name, which a yes quotes, or empty where this is not a reading. */
    public function agreement(): string
    {
        $about = $this->about;

        return $about !== null && $about->isAReading() ? $this->agreement : '';
    }

    /** Every service the install or update asked about would have take a privileged shape, or none. */
    public function shapesTaken(): TheShapesTaken
    {
        return match (true) {
            $this->about instanceof APluginInstall => $this->about->taking(),
            $this->about instanceof AnUpdate => $this->about->install()->taking(),
            default => TheShapesTaken::these(),
        };
    }

    /** Every approval the install, update or recipe run asked about would need, or none. */
    public function approvals(): PluginLines
    {
        return match (true) {
            $this->about instanceof APluginInstall => $this->about->approvals(),
            $this->about instanceof AnUpdate => $this->about->install()->approvals(),
            $this->about instanceof ARecipeRun => $this->about->recipe()->approvals(),
            default => PluginLines::none(),
        };
    }

    /** Whether this is a reading of an install, which a yes to installing may quote. */
    public function readsAnInstall(): bool
    {
        return $this->about instanceof APluginInstall && $this->about->isAReading();
    }

    /** Whether this is a reading of updating that plugin, which a yes to updating it may quote. */
    public function readsAnUpdateOf(APlugin $plugin): bool
    {
        return $this->about instanceof AnUpdate && $this->about->isAReading() && $this->about->plugin() === $plugin->id();
    }

    /** Whether this is a reading of removing that plugin, which a yes to removing it may quote. */
    public function readsARemovalOf(APlugin $plugin): bool
    {
        return $this->about instanceof APluginRemoval && $this->about->isAReading() && $this->about->plugin() === $plugin->id();
    }

    /** Whether this is a reading of proving that plugin again, which a yes to proving it may quote. */
    public function readsAProofOf(APlugin $plugin): bool
    {
        return $this->about instanceof APluginReproof && $this->about->isAReading() && $this->about->plugin() === $plugin->id();
    }

    /** Whether this is a reading of running that recipe of that plugin, which a yes to running it may quote. */
    public function readsARunOf(APlugin $plugin, ARecipe $recipe): bool
    {
        return $this->about instanceof ARecipeRun && $this->about->isAReading() && $this->about->plugin()->named() === $plugin->id() && $this->about->recipe()->id() === $recipe->id();
    }

    /**
     * Say what happens for the listing alone and for each act it can be about, and get back what you built.
     *
     * @template TListed of object
     * @template TInstall of object
     * @template TUpdate of object
     * @template TRemoval of object
     * @template TProof of object
     * @template TRun of object
     *
     * @param Closure(): TListed                $listed
     * @param Closure(APluginInstall): TInstall $install
     * @param Closure(AnUpdate): TUpdate        $update
     * @param Closure(APluginRemoval): TRemoval       $removal
     * @param Closure(APluginReproof): TProof          $proof
     * @param Closure(ARecipeRun): TRun                $run
     *
     * @return TListed|TInstall|TUpdate|TRemoval|TProof|TRun
     */
    public function either(Closure $listed, Closure $install, Closure $update, Closure $removal, Closure $proof, Closure $run): object
    {
        return match (true) {
            $this->about instanceof APluginInstall => $install($this->about),
            $this->about instanceof AnUpdate => $update($this->about),
            $this->about instanceof APluginRemoval => $removal($this->about),
            $this->about instanceof APluginReproof => $proof($this->about),
            $this->about instanceof ARecipeRun => $run($this->about),
            default => $listed(),
        };
    }
}
