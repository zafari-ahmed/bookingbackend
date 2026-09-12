<div class="slot-card h-full" :class="slotClass(cell)"
     @click="cell.type==='available' ? openAvailable(cell, court) : (cell.booking && openBooking(cell.booking.id))">
    <template x-if="cell.type==='available'">
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wide">Available</div>
        </div>
    </template>
    <template x-if="cell.type==='booking'">
        <div class="min-w-0">
            <div class="truncate text-[11px] font-bold" x-text="timeLabel(cell.booking.start_time)+' – '+timeLabel(cell.booking.end_time)"></div>
            <div class="truncate text-sm font-extrabold" x-text="cell.booking.member?.name"></div>
            <div class="truncate text-[11px]" x-text="cell.booking.member?.member_number"></div>
            <div class="truncate text-[11px] font-bold" x-text="money(cell.booking.total_amount)"></div>
            <!-- <div class="mt-1 badge" :class="'badge-'+cell.booking.tone" x-text="cell.booking.label"></div> -->
        </div>
    </template>
    <template x-if="cell.type==='block'"><div class="text-xs font-bold">Blocked</div></template>
</div>
