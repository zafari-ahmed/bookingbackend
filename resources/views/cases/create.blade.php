<x-layouts::app
    title="Add New Case"
    :back-link="route('dashboard')"
    back-label="Back to Dashboard"
>
    {{-- Two-column form in a centred card; single column below lg (§7). --}}
    <form method="POST" action="{{ route('cases.store') }}" enctype="multipart/form-data"
          class="mx-auto grid w-full max-w-[880px] gap-6">
        @csrf

        <section class="card px-5 py-6 sm:px-7">
            <header class="border-b border-border pb-4">
                <h2 class="text-lg font-bold tracking-tight text-navy">Complainant Details</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    Recorded at the AC office front desk. CNIC is optional but speeds up duplicate checks.
                </p>
            </header>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <x-form-field
                    name="complainant_name"
                    label="Full Name"
                    placeholder="e.g. Abdul Rasheed Memon"
                    required
                />

                <x-form-field
                    name="complainant_cnic"
                    label="CNIC Number"
                    placeholder="00000-0000000-0"
                    hint="Format 00000-0000000-0"
                    mono
                />

                <x-form-field
                    name="complainant_phone"
                    label="Phone Number"
                    type="tel"
                    placeholder="0300-0000000"
                    mono
                    required
                />

                <x-form-field
                    name="complainant_address"
                    label="Address"
                    placeholder="Union council, town or village"
                />
            </div>
        </section>

        <section class="card px-5 py-6 sm:px-7">
            <header class="border-b border-border pb-4">
                <h2 class="text-lg font-bold tracking-tight text-navy">Case Details</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    The summary below appears in the case register and in every notification sent to the receiving department.
                </p>
            </header>

            <div class="mt-5 grid gap-5">
                <x-form-field
                    name="issue_summary"
                    label="Issue Summary"
                    type="textarea"
                    :rows="5"
                    placeholder="Describe the complaint, the location and any action already taken."
                    hint="Minimum 20 characters."
                    required
                />

                <div class="grid items-start gap-x-5 gap-y-1.5 lg:grid-cols-2 lg:grid-rows-[auto_auto_auto]">
                    <x-form-field
                        name="department_id"
                        label="Assign to Department"
                        type="select"
                        placeholder-option="Select a department"
                        :options="$departments->pluck('name', 'id')"
                        class="lg:row-span-3 lg:grid-rows-subgrid lg:gap-y-0"
                        required
                    />

                    <x-priority-selector :priorities="$priorities" class="lg:row-span-3 lg:grid-rows-subgrid lg:gap-y-0" />
                </div>

                <x-file-upload />
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('dashboard') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
            <button type="submit" class="btn-primary w-full justify-center sm:w-auto">Log Case</button>
        </div>
    </form>
</x-layouts::app>
