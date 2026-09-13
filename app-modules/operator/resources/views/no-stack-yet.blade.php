<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ __('connection.no_stacks') }}</native:text>
    <native:text>{{ __('connection.setup_is_at_the_machine') }}</native:text>
    <native:text>{{ __('connection.no_stacks_action') }}</native:text>
    <native:button label="{{ __('connection.pair') }}" @navigate='/pair/scanned' />
    <native:button label="{{ __('connection.pair_by_typing') }}" @navigate='/pair/typed' />
</native:column>
