<x-filament-panels::page>
    <x-filament-panels::form>
        <p>Check the live page: <a href="{{ $this->liveLink }}" target="_blank" class="inline hover:text-blue-600 hover:underline">{{ $this->liveLink }}</a>
        <p class="text-xs">Please note, the save button does not give you a success feedback, just a failure.</p></p>

        {{ $this->form }}
        <xfilament-panels:x-filament-panels::header>
            Preview:
        </xfilament-panels:x-filament-panels::header>
        <div
            class="bg-white p-2 prose prose-h1:font-bold prose-h1:text-xl prose-a:text-blue-600 prose-p:text-justify prose-img:rounded-xl">
            {!! $this->html  !!}
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
