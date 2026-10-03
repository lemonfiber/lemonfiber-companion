<x-operator::screen-opens :title="__('navigation.menu.whats_new')" />

<x-operator::content>
    {{-- The filters first, because what they leave shown is also what Mark all
         seen clears. The stacks are offered only where there is more than one. --}}
    <x-design::chips>
        @forelse ($this->news()->kinds as $filter)
            <x-design::chip label="{{ __($filter->said) }}" :tap="$filter->tap" :chosen="$filter->chosen" />
        @empty
            {{-- Nothing: every kind and each kind are always offered. --}}
        @endforelse
    </x-design::chips>

    @if ($this->news()->stacks !== [])
        <x-design::chips>
            @forelse ($this->news()->stacks as $filter)
                <x-design::chip label="{{ $filter->said === '' ? $filter->name : __($filter->said) }}" :tap="$filter->tap" :chosen="$filter->chosen" />
            @empty
                {{-- Nothing: this is drawn only where there are stacks to offer. --}}
            @endforelse
        </x-design::chips>
    @endif

    @if ($this->news()->hasAnythingNew())
        <x-design::action label="{{ __('news.mark_all_seen') }}" tap="markAllSeen()" tone="tonal" />
    @endif

    {{-- A stack that could not be read comes before anything listed, so nothing
         missing from below reads as nothing new. --}}
    @forelse ($this->news()->unreached as $unreached)
        <x-design::row
            :headline="__('news.unreached', ['stack' => $unreached->stack])"
            :supporting="$unreached->ago === '' ? '' : __('news.unreached_since', ['ago' => trans_choice($unreached->ago, $unreached->count)])"
        />
    @empty
        {{-- Nothing: every stack shown was read. --}}
    @endforelse

    @forelse ($this->news()->sections as $section)
        <x-design::section :label="__($section->said)">
            @forelse ($section->rows as $row)
                {{-- Heard as what it says and where, since one title can be new on two stacks. --}}
                <x-design::row
                    :headline="$row->says->says === '' ? $row->says->words : __($row->says->says, $row->says->with)"
                    :supporting="__('news.item_beside', ['kind' => __($row->kind), 'stack' => $row->stack])"
                    :answers-to="($row->says->says === '' ? $row->says->words : __($row->says->says, $row->says->with)) . ', ' . __('news.item_beside', ['kind' => __($row->kind), 'stack' => $row->stack])"
                    :tap="$row->tap"
                />
            @empty
                {{-- Nothing: a section is drawn only for a kind with something new. --}}
            @endforelse
        </x-design::section>
    @empty
        @if ($this->news()->isEmpty())
            <x-design::heading>{{ __('news.nothing_new') }}</x-design::heading>
            <x-design::body>{{ __('news.nothing_new_explained') }}</x-design::body>
        @endif
    @endforelse

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />

    @if ($this->waitsForTheNextFrame())
        {{-- This frame read one stack, and another shown is still to be read. --}}
        <x-operator::the-next-frame />
    @endif
</x-operator::content>
