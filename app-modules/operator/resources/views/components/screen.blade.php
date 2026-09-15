{{--
    The chrome every screen sits in.

    Twelve screens opened with the same column, the same heading, and the same
    two branches — the session that has ended (`N1-R44`) and the obstacle that
    stopped the reading (`N1-R10`, `N1-R3`). Written out twelve times those are
    twelve places for one of them to drift, and `N1-R1` asks for parity across
    surfaces rather than parity by everybody remembering.

    **The bars are top-level siblings and their titles are attributes.** Both
    are the package's contract rather than a preference: the screen's bars are
    hoisted out of the content tree by type, from the root's own children, and
    a bar nested inside a column is not one of those — it stays in the content
    and is drawn inline, which is a back arrow floating in the middle of a
    screen and a title underneath the status bar. Safe-area handling is what
    hoisting buys, so a bar that is not hoisted is also a bar with no inset.

    Navigation is a `url`, not a tap handler, for the same reason: the platform
    owns the selected state and the back gesture, and it can only own them if it
    is told where each item goes.

    The brand is one colour and the rest is the platform's — `DES-R24` through
    `DES-R26`. The ground, the type and the spacing are whatever the reader has
    set, which is what a platform component is for.
--}}
<native:top-bar title="{{ $title }}" />

<native:scroll-view class="w-full">
    {{-- The padding is on a column inside rather than on the scroll view. A
         scroll view is a viewport: insetting it insets the scrolling area, not
         what scrolls through it, so the text ran to the edge of the handset. --}}
    <native:column class="w-full gap-4 px-6 py-4">
    @unless ($signedIn)
        {{-- The session has ended, so nothing was asked and there is nothing
             to report. The remedy is a screen rather than a sentence. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <x-operator::action label="{{ __('connection.sign_in') }}" :goes="$signInGoesTo" />
    @elseif ($met !== '')
        {{-- What stood in the way and what to do about it, both off the
             obstacle — so no screen describes a condition differently from
             the one beside it. --}}
        <x-operator::emphasis>{{ __($met) }}</x-operator::emphasis>
        <native:text>{{ __($remedy) }}</native:text>

        {{-- The action is offered and the failure reported, rather than the
             action being taken away because the stack is unreachable. Without
             it the only way back is leaving and returning, which `N1-R27`
             names separately as what a screen must not rely on. --}}
        <x-operator::action label="{{ __('health.ask_again') }}" tap="{{ $askAgain }}" />
    @else
        {{ $slot }}
        @endunless
    </native:column>
</native:scroll-view>

@if ($goes !== null)
    {{-- Where else this machine can be read. Present on every stack-scoped
         screen so that moving between them is a property of the app rather
         than of whichever screen thought to offer a button back. --}}
    <native:bottom-nav>
        <native:bottom-nav-item id="health" label="{{ __('navigation.health') }}" url="{{ $goes->health() }}" icon="home" />
        <native:bottom-nav-item id="services" label="{{ __('navigation.services') }}" url="{{ $goes->services() }}" icon="apps" />
        <native:bottom-nav-item id="updates" label="{{ __('navigation.updates') }}" url="{{ $goes->updates() }}" icon="download" />
        <native:bottom-nav-item id="repairs" label="{{ __('navigation.repairs') }}" url="{{ $goes->repairs() }}" icon="build" />
    </native:bottom-nav>
@endif
