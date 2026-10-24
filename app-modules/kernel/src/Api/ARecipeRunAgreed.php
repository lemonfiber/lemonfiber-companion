<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Running one of a plugin's demand recipes, agreed to against the reading the operator was shown.
 *
 * Two agreements, as for an install ({@see APluginInstallAgreed}): the yes
 * quotes the reading's name, and each value the recipe would carry elsewhere
 * is approved as itself. An approval given at install covers no run.
 */
final readonly class ARecipeRunAgreed
{
    private function __construct(private string $plugin, private string $recipe, private string $agreement, private PluginLines $approved) {}

    /** Agreed against that reading of running this recipe of this plugin; anything else is refused. */
    public static function after(ThePlugins $reading, APlugin $plugin, ARecipe $recipe, PluginLines $approved): self
    {
        if (! $reading->readsARunOf($plugin, $recipe) || $reading->agreement() === '') {
            throw APluginActWasNotRehearsed::before(ExtendingIt::Run);
        }

        return new self($plugin->id(), $recipe->id(), $reading->agreement(), $approved->alsoIn($reading->approvals()));
    }

    /** The plugin, by its id. */
    public function plugin(): string
    {
        return $this->plugin;
    }

    /** The recipe, by its id within the plugin. */
    public function recipe(): string
    {
        return $this->recipe;
    }

    /** The name of the reading agreed to. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** Every value approved, as the reading spells each. */
    public function approved(): PluginLines
    {
        return $this->approved;
    }
}
