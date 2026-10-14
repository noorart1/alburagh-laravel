{{-- pages.page plus the action modals (export/import): Filament leaves those to its
     table view on table pages, and this list renders its own table. --}}
<x-filament-panels::page>
    {{ $this->content }}

    <x-filament-actions::modals />
</x-filament-panels::page>
