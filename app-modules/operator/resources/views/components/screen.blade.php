{{--
    The chrome every screen sits in.

    Twelve screens opened with the same column, the same heading, and the same
    two branches — the session that has ended (`N1-R44`) and the obstacle that
    stopped the reading (`N1-R10`, `N1-R3`). Written out twelve times, those are
    twelve places for one of them to drift, and `N1-R1` asks for parity across
    surfaces rather than parity by everybody remembering.

    It takes plain values rather than the screen. A component's `$this` is the
    component, not the `NativeComponent` the view is bound to, so a destination
    arrives here as the string it already is. `@tap` is different and needs no
    binding: it names a method on the screen for the navigation stack to call,
    not a closure this template could hold.

    The brand is one colour and the rest is the platform's — `DES-R24` through
    `DES-R26`. `accent` is `lemon`; the ground, the type and the spacing are
    whatever the reader has set, which is what a platform component is for.
--}}

<native:column class="w-full h-full">
    <native:top-bar>
        <native:top-bar-title>{{ $title }}</native:top-bar-title>
    </native:top-bar>

    <native:scroll-view class="w-full grow gap-4 p-6">
        @unless ($signedIn)
            {{-- The session has ended, so nothing was asked and there is nothing
                 to report. The remedy is a screen rather than a sentence. --}}
            <native:text>{{ __('connection.session_has_ended') }}</native:text>
            <native:button label="{{ __('connection.sign_in') }}" @navigate="$signInGoesTo" />
        @elseif ($met !== '')
            {{-- What stood in the way and what to do about it, both off the
                 obstacle — so no screen describes a condition differently from
                 the one beside it. --}}
            <native:text class="font-bold">{{ __($met) }}</native:text>
            <native:text>{{ __($remedy) }}</native:text>

            {{-- The action is offered and the failure reported, rather than the
                 action being taken away because the stack is unreachable.
                 Without it the only way back is leaving and returning, which
                 `N1-R27` names separately as what a screen must not rely on. --}}
            <native:button label="{{ __('health.ask_again') }}" @tap="{{ $askAgain }}" />
        @else
            {{ $slot }}
        @endunless
    </native:scroll-view>

    @if ($goes !== null)
        {{-- Where else this machine can be read. Present on every stack-scoped
             screen so that moving between them is a property of the app rather
             than of whichever screen thought to offer a button back. --}}
        <native:bottom-nav>
            <native:bottom-nav-item
                label="{{ __('navigation.health') }}"
                icon="heart"
                :selected="$here === 'health'"
                @navigate.replace="$goes->health()"
            />
            <native:bottom-nav-item
                label="{{ __('navigation.services') }}"
                icon="square.grid.2x2"
                :selected="$here === 'services'"
                @navigate.replace="$goes->services()"
            />
            <native:bottom-nav-item
                label="{{ __('navigation.updates') }}"
                icon="arrow.down.circle"
                :selected="$here === 'updates'"
                @navigate.replace="$goes->updates()"
            />
            <native:bottom-nav-item
                label="{{ __('navigation.repairs') }}"
                icon="wrench"
                :selected="$here === 'repairs'"
                @navigate.replace="$goes->repairs()"
            />
        </native:bottom-nav>
    @endif
</native:column>
