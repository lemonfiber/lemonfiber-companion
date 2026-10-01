{{-- The list of stacks, over the screen while it is open. A row closes it and
     opens where it leads in the same press; the current stack only closes it. --}}
<native:bottom-sheet :visible="$choosing" detents="medium" @dismiss="stopChoosingAStack()">
    <native:column class="w-full gap-4 p-4">
        @if ($choosing)
            <x-design::title>{{ __('navigation.your_stacks') }}</x-design::title>

            <x-design::section>
                @forelse ($stacks as $one)
                    <x-design::row
                        :headline="$one->name"
                        :supporting="__($one->word)"
                        :tone="$one->tone()"
                        :icon="$one->icon()"
                        :ios-icon="$one->iosIcon()"
                        :tap="$one->pressed()"
                        :answers-to="$one->current ? __('connection.current_stack', ['stack' => $one->name]) : __('connection.open_stack', ['stack' => $one->name])"
                    />
                @empty
                    {{-- Nothing: a screen about a stack is only drawn where one is paired. --}}
                @endforelse

                <x-design::row
                    :headline="__('navigation.switcher.add')"
                    :answers-to="__('navigation.switcher.add')"
                    icon="add"
                    ios-icon="plus"
                    tap="addAStack()"
                />
            </x-design::section>

            {{-- One line for every stack in the list, because every one is reached
                 the same way: pinned to the certificate its pairing named, which
                 only an encrypted address presents, and `Pairing` refuses any
                 other. --}}
            <x-design::note>{{ __('connection.encrypted') }}</x-design::note>
        @endif
    </native:column>
</native:bottom-sheet>
