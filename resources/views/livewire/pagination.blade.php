@if ($paginator->hasPages())
    <nav aria-label="Halaman katalog" class="flex flex-wrap items-center justify-center gap-3 pt-2"
         x-on:click="if ($event.target.closest('button')) $root.closest('section')?.scrollIntoView({ block: 'start' })">
        <button type="button" wire:click="previousPage" @disabled($paginator->onFirstPage()) class="btn-outline min-w-[132px]">
            Sebelumnya
        </button>
        <span class="px-2 text-[15px]">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        <button type="button" wire:click="nextPage" @disabled(! $paginator->hasMorePages()) class="btn-outline min-w-[132px]">
            Berikutnya
        </button>
    </nav>
@endif
