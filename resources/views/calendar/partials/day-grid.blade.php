<div x-show="loading" class="card p-4">
    <div class="grid gap-3">
        <div class="skeleton h-16"></div>
        <div class="skeleton h-24"></div>
        <div class="skeleton h-24"></div>
    </div>
</div>

<div x-show="!loading && view==='day'" class="card overflow-auto">
    <template x-if="!data.courts?.length">
        <div class="p-8">No courts match these filters.</div>
    </template>

    <div class="calendar-horizontal" x-show="data.courts?.length && layout==='horizontal'">
        <div class="sticky top-0 z-10 grid border-b border-slate-100 bg-white" :style="`grid-template-columns: 180px repeat(${data.slots?.length||1}, minmax(118px, 1fr))`">
            <div class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-slate-400">Sport / Court</div>
            <template x-for="slot in data.slots" :key="'hs'+slot">
                <div class="px-2 py-3 text-center text-xs font-bold text-slate-500" x-text="timeLabel(slot)"></div>
            </template>
        </div>
        <template x-for="court in data.courts" :key="'hr'+court.id">
            <div class="grid border-b border-slate-100" :style="`grid-template-columns: 180px repeat(${data.slots?.length||1}, minmax(118px, 1fr))`">
                <div class="sticky left-0 z-10 bg-white px-4 py-4">
                    <div class="text-xs text-slate-400" x-text="court.sport.name"></div>
                    <div class="font-bold text-navy" x-text="court.name"></div>
                </div>
                <template x-for="cell in court.cells" :key="'h'+court.id+cell.slot+cell.type">
                    <div class="p-1" :style="`grid-column: span ${cell.span}`">
                        @include('calendar.partials.slot-cell')
                    </div>
                </template>
            </div>
        </template>
    </div>

    <div class="calendar-vertical" x-show="data.courts?.length && layout==='vertical'"
         :style="`grid-template-columns: 100px repeat(${data.courts?.length||1}, minmax(160px, 1fr)); grid-template-rows: auto repeat(${data.slots?.length||1}, 48px)`">
        <div class="sticky top-0 left-0 z-30 bg-white px-3 py-3 text-xs font-bold uppercase tracking-wide text-slate-400" style="grid-column: 1; grid-row: 1">Time</div>
        <template x-for="court in data.courts" :key="'vh'+court.id">
            <div class="sticky top-0 z-20 border-b border-slate-100 bg-white px-3 py-3" :style="`grid-column: ${courtCol(court)}; grid-row: 1`">
                <div class="truncate text-xs text-slate-400" x-text="court.sport.name"></div>
                <div class="truncate font-bold text-navy" x-text="court.name"></div>
            </div>
        </template>
        <template x-for="slot in data.slots" :key="'vt'+slot">
            <div class="sticky left-0 z-10 flex items-start justify-end bg-white px-3 pt-2 text-xs font-bold text-slate-500 whitespace-nowrap" :class="showTimeLabel(slot) ? 'border-b border-slate-100' : ''" :style="`grid-column: 1; grid-row: ${slotRow(slot)}`" x-text="showTimeLabel(slot) ? timeLabel(slot) : ''"></div>
        </template>
        <template x-for="court in data.courts" :key="'vc'+court.id">
            <template x-for="cell in court.cells" :key="'v'+court.id+cell.slot+cell.type">
                <div class="p-1" :style="`grid-column: ${courtCol(court)}; grid-row: ${slotRow(cell.slot)} / span ${cell.span}`">
                    @include('calendar.partials.slot-cell')
                </div>
            </template>
        </template>
    </div>
</div>
