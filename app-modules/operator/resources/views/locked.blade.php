{{-- The lock and nothing behind it: one sentence and one button. No top bar,
     so no back arrow and no edge swipe; no menu and no tabs; nothing about a
     stack and nothing kept. --}}
<x-operator::content>
    <x-design::title>{{ __('device.unlock_reason') }}</x-design::title>

    {{-- A button rather than an automatic retry. An operator who dismissed the
         prompt meant it, and a screen that asked again at once is what teaches
         people to turn a feature off. --}}
    <x-design::action label="{{ __('device.unlock') }}" tap="tryToUnlock()" />
</x-operator::content>
