<div class="flex rounded-2xl bg-mist p-1" title="Calendar layout">
    <button type="button" class="btn px-3" :class="layout==='horizontal' ? 'btn-primary' : 'btn-ghost'" @click="setLayout('horizontal')" title="Horizontal view">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v12H4zM8 6v12M12 6v12M16 6v12"/>
        </svg>
        <span class="sr-only">Horizontal</span>
    </button>
    <button type="button" class="btn px-3" :class="layout==='vertical' ? 'btn-primary' : 'btn-ghost'" @click="setLayout('vertical')" title="Vertical view">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v12H4zM4 10h16M4 14h16"/>
        </svg>
        <span class="sr-only">Vertical</span>
    </button>
</div>
