@props([
    'name' => 'attachments',
    'label' => 'Attachments',
    'hint' => null,
    'max' => 10,
])

@php
    $max = (int) $max;
    $maxMb = round(\App\Http\Requests\AttachmentRules::maxKilobytes() / 1024);
    $accept = collect(\App\Http\Requests\AttachmentRules::ALLOWED_EXTENSIONS)
        ->map(fn (string $extension): string => '.'.$extension)
        ->implode(',');
    $types = strtoupper(implode(', ', \App\Http\Requests\AttachmentRules::ALLOWED_EXTENSIONS));
    $hint ??= 'Photographs, scanned applications or written complaints · '.$types.' up to '.$maxMb.' MB each.';
@endphp

{{-- Dashed drop zone, then a file chip per selection (design-system.md §5). --}}
<div
    x-data="{
        selected: [],
        files: [],
        dragging: false,
        limitMessage: '',
        max: {{ $max }},
        add(fileList) {
            const incoming = Array.from(fileList);
            incoming.forEach((file) => {
                const duplicate = this.selected.some((existing) =>
                    existing.name === file.name
                    && existing.size === file.size
                    && existing.lastModified === file.lastModified
                );

                if (! duplicate) {
                    this.selected.push(file);
                }
            });

            if (this.selected.length > this.max) {
                this.selected = this.selected.slice(0, this.max);
                this.limitMessage = 'Only the first ' + this.max + ' files were kept.';
            } else {
                this.limitMessage = '';
            }

            this.writeInput();
        },
        writeInput() {
            const transfer = new DataTransfer();
            this.selected.forEach((file) => transfer.items.add(file));
            $refs.input.files = transfer.files;
            this.files = this.selected.map((file, index) => ({
                index,
                name: file.name,
                size: file.size > 1048576
                    ? (file.size / 1048576).toFixed(1) + ' MB'
                    : Math.max(1, Math.round(file.size / 1024)) + ' KB',
            }));
        },
        preview(index) {
            const file = this.selected[index];
            if (! file) {
                return;
            }

            const url = URL.createObjectURL(file);
            window.open(url, '_blank', 'noopener,noreferrer');
            setTimeout(() => URL.revokeObjectURL(url), 60000);
        },
        remove(index) {
            this.selected.splice(index, 1);
            this.limitMessage = '';
            this.writeInput();
        },
    }"
    class="grid gap-2"
>
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <span class="field-label">{{ $label }}</span>
        <span class="meta" x-text="files.length + ' of ' + max + ' file' + (max === 1 ? '' : 's') + ' selected'"></span>
    </div>

    <p class="rounded-control border border-pending-text/20 bg-pending-bg px-3.5 py-2.5 text-xs leading-relaxed text-pending-text">
        Files cannot be deleted after they are uploaded. You can select or drop several files at once — use the view icon on each chip to check it is the right one before you submit.
    </p>

    <label
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="add($event.dataTransfer.files)"
        class="flex min-w-0 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-card border-[1.5px] border-dashed px-4 py-6 text-center transition sm:px-5 sm:py-7"
        :class="dragging ? 'border-teal bg-teal-tint' : 'border-border bg-cream'"
    >
        <x-icon name="upload" class="size-6 text-text-muted" />
        <span class="text-sm font-bold text-text-secondary">
            <span class="sm:hidden">Tap to choose files</span>
            <span class="hidden sm:inline">Drag files here or click to browse</span>
        </span>
        <span class="meta hidden sm:inline">Hold Ctrl (or Cmd on Mac) to pick several files, or drop a whole group. Adding more keeps the ones already selected.</span>
        <span class="meta sm:hidden">You can pick several files at once. Adding more keeps the ones already selected.</span>
        <span class="meta">{{ $hint }}</span>

        <input
            x-ref="input"
            type="file"
            name="{{ $name }}[]"
            multiple
            accept="{{ $accept }}"
            @change="add($event.target.files)"
            class="sr-only"
        >
    </label>

    <p class="field-error" x-show="limitMessage" x-cloak x-text="limitMessage"></p>

    <div x-show="files.length" x-cloak class="flex flex-wrap gap-2">
        <template x-for="file in files" :key="file.index + '-' + file.name">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-teal/25 bg-teal-tint py-1 pl-3.5 pr-1.5 text-xs font-semibold text-teal">
                <span class="max-w-[180px] truncate" x-text="file.name"></span>
                <span class="font-normal opacity-70" x-text="file.size"></span>
                <button type="button" @click.prevent="preview(file.index)"
                        class="flex size-8 items-center justify-center rounded-full text-teal transition hover:bg-teal/15"
                        :aria-label="'View ' + file.name">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
                <button type="button" @click.prevent="remove(file.index)"
                        class="flex size-8 items-center justify-center rounded-full text-teal transition hover:bg-teal/15"
                        :aria-label="'Remove ' + file.name">&times;</button>
            </span>
        </template>
    </div>

    @error($name)
        <p class="field-error">{{ $message }}</p>
    @enderror
    @error($name.'.*')
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
