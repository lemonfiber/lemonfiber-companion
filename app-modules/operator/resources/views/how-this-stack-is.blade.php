<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    <x-operator::emphasis>{{ __($this->answer()->overall) }}</x-operator::emphasis>

    {{-- N2-R9: the families this run has something to say about, so that a
         stuck queue or a provider gone quiet is one tap away rather than
         eight rows of scrolling. Only the families with findings are
         offered — a control leading to a blank screen teaches an operator
         that the row is not worth reading — and the one being read is also
         the way back out, so there is no separate "all" to go and find. --}}
    <native:column class="w-full gap-2">
        @forelse ($this->families() as $family)
            <x-operator::entry>
                <x-operator::action label="{{ __('health.family_and_count', ['family' => __($family->said), 'count' => $family->howMany]) }}" tap="read('{{ $family->family }}')" />

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
            </x-operator::entry>
        @empty
            {{-- Deliberately nothing. No family has anything to say only
                 where the run found nothing at all, and the list below
                 says so — a second sentence here would be this app filling
                 space with a line somebody has to translate. --}}
        @endforelse
    </native:column>

    @forelse ($this->findings() as $finding)
        <x-operator::entry>
            {{-- Which part of the machine, and which service under it. The
                 same check runs against whichever service fills a role, so
                 the title never names one — an operator with nineteen of
                 them needs the name beside it. Rendered rather than
                 translated: it is the stack's own name for the service. --}}
            <x-operator::note>
                {{ __($finding->about) }}@if ($finding->service !== '') · {{ $finding->service }}@endif
            </x-operator::note>
            <x-operator::emphasis>{{ $finding->title }}</x-operator::emphasis>
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
                <x-operator::note>{{ __('health.because_of', ['title' => $finding->because]) }}</x-operator::note>
            @endif

            @if ($finding->explainsItself())
                <native:text>{{ $finding->meaning }}</native:text>
                <x-operator::note>{{ $finding->code }}</x-operator::note>

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

            @if ($finding->underneath !== '')
                {{-- G4-R4: the technical detail, available and not leading.
                     Last on the row, under the plain explanation and under
                     what to try — which is what *must not lead* means on a
                     surface with one column. Somebody who knows what the
                     line says now has it; everybody else has already read
                     the sentence that was written for them. --}}
                <x-operator::note>{{ __('health.what_it_says_underneath') }}</x-operator::note>
                <x-operator::note>{{ $finding->underneath }}</x-operator::note>
            @endif

            {{-- N2-R10: the logs, offered from the finding that is already
                 about this service. Only where there is one — a check about
                 the machine itself has no scrollback to read, and a button
                 that led to an empty window would be the row teaching an
                 operator not to trust the row. --}}
            @if ($finding->service !== '')
                <x-operator::action label="{{ __('health.what_a_service_said') }}" :goes="$this->logsOf($finding->service)" />
            @endif
        </x-operator::entry>
    @empty
        <native:text>{{ __('health.no_findings') }}</native:text>
    @endforelse

    {{-- Under the findings rather than above them: somebody who has just
         fixed something scrolls to the end of what was wrong, and that is
         where they want to ask whether it took. --}}
    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />

    {{-- The three readings the bar under this screen does not carry.

         `N2-R7`'s services, `N2-R4`'s repairs and `N2-R15`'s updates are three
         of the bar's four items, and this screen used to repeat all three as
         full-width buttons — the same destinations offered twice, once where
         the platform draws navigation and once in the middle of the reading.
         Seven controls under the findings, four of them leading somewhere the
         bar already leads.

         The bar is how a person moves between the readings of one machine.
         These three are the readings it has no room for, and they stay.
         {@see \Tests\Support\WhereAScreenCanSendYou} reads the bar as edges,
         so `F12` is what says removing the other three stranded nothing — a
         walk blind to the bar would have insisted they stay.

         N2-R11: what the house asked for, one tap from the machine it is
         about. Here rather than on the list because requests belong to one
         stack and the list is about several — and an operator looking at a
         machine is already holding the question the household asks them. --}}
    <x-operator::action label="{{ __('household.asked_for') }}" :goes="$this->goes()->requests()" />

    {{-- N2-R9: what stopped coming in. Reachable from the machine it is
         about rather than from the list, for the reason the requests button
         is — and reachable at all is the requirement: a stack passing every
         check and a household getting nothing are not a contradiction, so
         this cannot live under the verdict above. --}}
    <x-operator::action label="{{ __('health.what_stopped') }}" :goes="$this->goes()->stuck()" />

    {{-- N2-R21: what is running here that this machine never declared. The
         requirement asks for these to be reachable, and this is where from
         — beside what the stack runs rather than inside it, because a
         container nobody declared is not one of the things this stack runs
         and the screen it leads to offers no verb against one. --}}
    <x-operator::action label="{{ __('health.what_else_is_running') }}" :goes="$this->goes()->elsewhere()" />
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
