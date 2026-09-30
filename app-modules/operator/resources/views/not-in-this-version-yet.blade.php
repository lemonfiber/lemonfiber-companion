<x-operator::screen-opens :title="__($this->which()->said())" />

<x-operator::content>
    <x-design::body>{{ __('device.not_in_this_version') }}</x-design::body>

    <x-design::link label="{{ __('connection.back_to_your_stacks') }}" :goes="$this->theListIsAt()" />
</x-operator::content>
