<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    <x-filament::section>
        <x-slot name="heading">كيف يصل الإشعار؟</x-slot>

        <ul class="fi-ta-text-item-label text-sm leading-7 list-disc ps-5">
            <li>
                <strong>داخل التطبيق:</strong> يُحفظ لكل مستلم ويظهر في شاشة الإشعارات
                عند فتح التطبيق — يعمل دائماً.
            </li>
            <li>
                <strong>على الهاتف (تنبيه فوري):</strong> يصل فقط لمن سجّل دخوله من
                التطبيق وقبِل إذن الإشعارات.
            </li>
        </ul>
    </x-filament::section>
</x-filament-panels::page>
