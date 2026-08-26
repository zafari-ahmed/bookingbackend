<x-layouts::app title="Two-Factor Authentication">
    <div class="mx-auto grid w-full max-w-[720px] gap-5">
        <section class="card px-5 py-6 sm:px-7">
            <header class="border-b border-border pb-4">
                <h2 class="text-lg font-bold tracking-tight text-navy">Two-Factor Authentication</h2>
                <p class="mt-1 text-sm leading-relaxed text-text-secondary">
                    This portal holds CNIC and complaint records, so accounts with administrative access must be
                    protected by a second factor before the case register opens.
                </p>
            </header>

            <div class="mt-5">
                <x-two-factor-panel
                    :enabled="$enabled"
                    :confirmed="$confirmed"
                    :qr-code-svg="$qrCodeSvg"
                    :recovery-codes="$recoveryCodes"
                    :can-disable="false"
                />

                @if ($confirmed)
                    <a href="{{ route('dashboard') }}" class="btn-primary mt-5 inline-flex justify-center">
                        Go to Dashboard
                    </a>
                @endif
            </div>
        </section>
    </div>
</x-layouts::app>
