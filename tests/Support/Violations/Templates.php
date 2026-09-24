<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use function sprintf;

use Tests\Support\Fixture;

/** The Blade templates, and the screens they make reachable. */
final readonly class Templates
{
    /**
     * The Blade rules.
     *
     * Written as templates in a module's own view directory, because that is
     * where the suite looks and because a `.blade.php` file is not a class —
     * nothing that reflects over namespaces can see one, which is the whole
     * reason these checks exist separately from the architecture rules.
     *
     * @return list<Fixture>
     */
    public static function templates(): array
    {
        $views = 'app-modules/operator/resources/views/Fixtures';

        return [
            Fixture::suite('F3', sprintf('%s/unknown-class.blade.php', $views), <<<'BLADE'
                <native:column class="w-full flex-nonsense">
                    <native:text>{{ __('health.overall.healthy') }}</native:text>
                </native:column>
                BLADE, 'every class in', 'unknown-class'),

            Fixture::suite('F3', sprintf('%s/unknown-tag.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:nonsense-tag />
                </native:column>
                BLADE, 'every tag in', 'unknown-tag'),

            Fixture::suite('F3', sprintf('%s/bare-tag.blade.php', $views), <<<'BLADE'
                <column class="w-full">
                    <native:text>{{ __('health.overall.healthy') }}</native:text>
                </column>
                BLADE, 'every element in', 'bare-tag'),

            Fixture::suite('F3', sprintf('%s/holds-logic.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    @php $count = 1; @endphp
                </native:column>
                BLADE, 'holds no logic', 'holds-logic'),

            Fixture::suite('F3', sprintf('%s/opens-a-web-view.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:webview src="https://example.test" />
                </native:column>
                BLADE, 'opens no web view', 'opens-a-web-view'),

            Fixture::suite('F3', sprintf('%s/literal-colour.blade.php', $views), <<<'BLADE'
                <native:column class="w-full bg-red-500">
                    <native:text>{{ __('health.overall.healthy') }}</native:text>
                </native:column>
                BLADE, 'names no literal colour', 'literal-colour'),

            // The one every other fixture here is invisible to. A class token
            // holding an echo is dropped unread, so the three rules above go
            // quiet together: this file names an unknown utility, a literal
            // colour and the accent set as text, and all three pass it.
            Fixture::suite('F9', sprintf('%s/runtime-class.blade.php', $views), <<<'BLADE'
                <native:column class="{{ $open ? 'bg-theme-accnt' : 'bg-red-500' }}">
                    <native:text class="{{ $open ? 'text-theme-accent' : '' }}">{{ __('health.overall.healthy') }}</native:text>
                </native:column>
                BLADE, 'F9 —', 'runtime-class'),

            // A field somebody types into, with nothing to say for itself. The
            // component the screens use rather than one they do not, because
            // this is the shape the rule used to pass over.
            Fixture::suite('F5', sprintf('%s/silent-control.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:outlined-text-input native:model="typed" />
                </native:column>
                BLADE, 'every control in', 'silent-control'),

            // Furniture handed something to do, which the table of component
            // kinds cannot see and the second reading can.
            Fixture::suite('F5', sprintf('%s/silent-tap.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:text @tap="open">x</native:text>
                </native:column>
                BLADE, 'every control in', 'silent-tap'),

            // A component nobody has classified, and deliberately one with
            // nothing to do: were it operable the rule above would fire too,
            // and two rules answering for one fixture proves neither.
            Fixture::suite('F11', sprintf('%s/unclassified-component.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:activity-indicator />
                </native:column>
                BLADE, 'every component in', 'unclassified-component'),

            Fixture::suite('F6', sprintf('%s/no-empty-state.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    @foreach ($findings as $finding)
                        <native:text>{{ $finding->title }}</native:text>
                    @endforeach
                </native:column>
                BLADE, 'every list in', 'no-empty-state'),

            // A slot echoed on one branch and not the other. Deliberately a
            // component with nothing else in it: the trap is that both arms
            // reach the device, so a fixture with a second control in it would
            // read as a component that draws too much rather than as one that
            // cannot drop what it was given.
            Fixture::suite('F13', sprintf('%s/a-slot-behind-a-branch.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    @if ($went->cameBack())
                        {{ $slot }}
                    @endif
                </native:column>
                BLADE, 'draws its slot on every branch', 'a-slot-behind-a-branch'),

            // The column a screen's content sits in, written out rather than
            // opened by the component that holds it.
            Fixture::suite('F16', sprintf('%s/a-content-column-written-out.blade.php', $views), <<<'BLADE'
                <native:column class="w-full gap-4 px-6 py-4">
                    <native:text>{{ __('health.overall.healthy') }}</native:text>
                </native:column>
                BLADE, 'no template but the component writes the content column out', 'a-content-column-written-out'),

            Fixture::suite('L1', sprintf('%s/english-sentence.blade.php', $views), <<<'BLADE'
                <native:column class="w-full">
                    <native:text>This stack cannot be reached from here.</native:text>
                </native:column>
                BLADE, 'reads its text from the translator', 'english-sentence'),
        ];
    }

    /**
     * A screen a person cannot tap their way to.
     *
     * Deliberately a well-behaved one. It announces its control, holds no
     * sentence of its own, calls nothing its class has not got, and offers a
     * way off it — so `F5`, `L1`, `F10` and the rule that every screen goes
     * somewhere all pass it; the destination it offers is one a template
     * already navigates to, so the rules about what points where pass it too.
     *
     * Every local rule this repository has is therefore satisfied by a screen
     * nobody can reach, which is the whole of what `F12` is for. Two files,
     * because a screen is a class and a template, and a walk holding one
     * without the other is walking half the graph.
     *
     * @return list<Fixture>
     */
    public static function screensNobodyCanReach(): array
    {
        $arrivesAt = 'can be reached from the one the app opens on';

        return [
            Fixture::suite('F12', 'app-modules/operator/src/Internal/Screens/Fixtures/AScreenNothingOpensOn.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Internal\Screens\Fixtures;

                use Illuminate\View\View;
                use Modules\Operator\Internal\AScreenWithoutAStack;

                use function view;

                final readonly class AScreenNothingOpensOn
                {
                    public function theListIsAt(): string
                    {
                        return AScreenWithoutAStack::TheList->value;
                    }

                    public function render(): View
                    {
                        return view('operator::Fixtures.a-screen-nothing-opens-on');
                    }
                }
                PHP, $arrivesAt, 'AScreenNothingOpensOn'),

            Fixture::suite('F12', 'app-modules/operator/resources/views/Fixtures/a-screen-nothing-opens-on.blade.php', <<<'BLADE'
                <native:column class="w-full h-full p-4 gap-4 bg-theme-background">
                    <native:button label="{{ __('connection.back_to_your_stacks') }}" @navigate="{{ $this->theListIsAt() }}" />
                </native:column>
                BLADE, $arrivesAt, 'a-screen-nothing-opens-on'),
        ];
    }
}
