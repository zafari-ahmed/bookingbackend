<div x-show="toast" x-cloak class="fixed bottom-5 right-5 z-50 rounded-2xl px-4 py-3 text-sm font-semibold text-white shadow-xl"
     :class="toast?.tone==='err' ? 'bg-red-600' : 'bg-navy'">
    <span x-text="toast?.message"></span>
</div>
<div x-show="confirm.open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-navy/40 p-4 sm:items-center">
    <div class="modal-sheet card w-full max-w-md p-6">
        <h3 class="font-display text-xl font-extrabold" x-text="confirm.title"></h3>
        <p class="mt-2 text-slate-600" x-text="confirm.message"></p>
        <p class="mt-1 text-sm text-slate-400" x-text="confirm.extra"></p>
        <div class="mt-5 flex justify-end gap-2">
            <button class="btn btn-ghost" @click="settle(false)">Keep</button>
            <button class="btn" :class="confirm.tone==='primary' ? 'btn-primary' : 'btn-danger'" @click="settle(true)" x-text="confirm.confirmText || 'Confirm'"></button>
        </div>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
