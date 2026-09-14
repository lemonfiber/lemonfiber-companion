<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ $this->stack()->name()->shown() }}</native:text>

    @unless ($this->isSignedIn())
        {{-- N1-R44: the session has ended, so nothing was asked and there is
             nothing to report. The remedy is a screen rather than a sentence. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <native:button
            label="{{ __('connection.sign_in') }}"
            @navigate="{{ $this->goes()->signIn() }}"
        />
    @elseif ($this->met() !== '')
        <native:text class="font-bold">{{ __($this->met()) }}</native:text>
        <native:text>{{ __($this->remedy()) }}</native:text>

        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @else
        <native:text class="font-bold">{{ __($this->overall()) }}</native:text>

        {{-- N2-R9: the families this run has something to say about, so that a
             stuck queue or a provider gone quiet is one tap away rather than
             eight rows of scrolling. Only the families with findings are
             offered — a control leading to a blank screen teaches an operator
             that the row is not worth reading — and the one being read is also
             the way back out, so there is no separate "all" to go and find. --}}
        <native:column class="w-full gap-2">
            @forelse ($this->families() as $family)
                <native:column class="w-full gap-1">
                    <native:button
                        label="{{ __('health.family_and_count', ['family' => __($family->said), 'count' => $family->howMany]) }}"
                        @tap="read('{{ $family->family }}')"
                    />

                    {{-- The accent is a fill, a bar, a selected state, and
                         never text (DES-R15). Written as its own element with
                         a static class rather than as a colour chosen inside a
                         ternary: the vocabulary check drops any class token
                         holding a runtime expression, and an EDGE class it
                         cannot read is one a typo turns into nothing at all,
                         silently, on a device. --}}
                    @if ($family->isOpen)
                        <native:column class="w-full h-1 bg-theme-accent" />
                    @endif
                </native:column>
            @empty
                {{-- Deliberately nothing. No family has anything to say only
                     where the run found nothing at all, and the list below
                     says so — a second sentence here would be this app filling
                     space with a line somebody has to translate. --}}
            @endforelse
        </native:column>

        @forelse ($this->findings() as $finding)
            <native:column class="w-full gap-1">
                {{-- Which part of the machine, and which service under it. The
                     same check runs against whichever service fills a role, so
                     the title never names one — an operator with nineteen of
                     them needs the name beside it. Rendered rather than
                     translated: it is the stack's own name for the service. --}}
                <native:text class="text-sm">
                    {{ __($finding->about) }}@if ($finding->service !== '') · {{ $finding->service }}@endif
                </native:text>
                <native:text class="font-bold">{{ $finding->title }}</native:text>
                {{-- The verdict and what it costs, on one line. They answer
                     different questions and a row showing only the first makes
                     two failures look alike where one puts data at risk. A row
                     with nothing graded — a pass, or a check that could not run
                     — carries no cost and shows the verdict alone. --}}
                <native:text>{{ __($finding->verdict) }}</native:text>

                @if ($finding->cost !== '')
                    <native:text class="text-sm font-bold">{{ __($finding->cost) }}</native:text>
                @endif

                {{-- N2-R3: what the core said about it, in the core's own
                     words. Rendered rather than translated — these are the
                     machine's sentences about the machine, and putting them
                     through the catalogue would mean this app inventing a
                     line for a check it has never heard of. --}}
                {{-- What explains this one, where the run says something does.
                     Without it an operator reads five broken things; with it
                     they read one broken thing and four services that noticed,
                     which is the row they should go and fix. --}}
                @if ($finding->because !== '')
                    <native:text class="text-sm">{{ __('health.because_of', ['title' => $finding->because]) }}</native:text>
                @endif

                @if ($finding->explainsItself())
                    <native:text>{{ $finding->meaning }}</native:text>
                    <native:text class="text-sm">{{ $finding->code }}</native:text>

                    {{-- A failure may carry no remedy at all, and that is a
                         sentence rather than blank space: the operator is
                         being told the machine knows what is wrong and has
                         nothing to suggest, which is what sends them to the
                         machine itself. --}}
                    @forelse ($finding->remedies as $remedy)
                        <native:text>{{ $remedy->action() }}</native:text>
                    @empty
                        <native:text>{{ __('health.nothing_to_try') }}</native:text>
                    @endforelse
                @endif

                {{-- N2-R10: the logs, offered from the finding that is already
                     about this service. Only where there is one — a check about
                     the machine itself has no scrollback to read, and a button
                     that led to an empty window would be the row teaching an
                     operator not to trust the row. --}}
                @if ($finding->service !== '')
                    <native:button
                        label="{{ __('health.what_a_service_said') }}"
                        @navigate="{{ $this->logsOf($finding->service) }}"
                    />
                @endif
            </native:column>
        @empty
            <native:text>{{ __('health.no_findings') }}</native:text>
        @endforelse

        {{-- Under the findings rather than above them: somebody who has just
             fixed something scrolls to the end of what was wrong, and that is
             where they want to ask whether it took. --}}
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />

        {{-- N2-R11: what the house asked for, one tap from the machine it is
             about. Here rather than on the list because requests belong to one
             stack and the list is about several — and an operator looking at a
             machine is already holding the question the household asks them. --}}
        <native:button
            label="{{ __('household.asked_for') }}"
            @navigate="{{ $this->goes()->requests() }}"
        />

        {{-- N2-R4: what this machine would put right, stated in full before
             anybody is asked to agree to any of it. --}}
        <native:button
            label="{{ __('health.would_put_right') }}"
            @navigate="{{ $this->goes()->repairs() }}"
        />

        {{-- N2-R9: what stopped coming in. Reachable from the machine it is
             about rather than from the list, for the reason the requests button
             is — and reachable at all is the requirement: a stack passing every
             check and a household getting nothing are not a contradiction, so
             this cannot live under the verdict above. --}}
        <native:button
            label="{{ __('health.what_stopped') }}"
            @navigate="{{ $this->goes()->stuck() }}"
        />

        {{-- N2-R7: what this machine is running, and the three verbs about it.
             Reachable from the machine rather than from the list, as the three
             above are. It is not under the verdict either: every check can pass
             on a machine where the one service somebody wants is switched off,
             which is exactly the evening this screen is for. --}}
        <native:button
            label="{{ __('health.what_it_runs') }}"
            @navigate="{{ $this->goes()->services() }}"
        />
    @endunless
</native:column>
