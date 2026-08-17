<x-filament-widgets::widget>
    <x-filament::section :heading="static::$heading">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-gray-500">
                    <tr>
                        <th class="text-right py-2">السبب</th>
                        <th class="text-right py-2">النوع</th>
                        <th class="text-right py-2">العدد</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->rows() as $row)
                        <tr class="border-t">
                            <td class="py-2">{{ $row['label'] }}</td>
                            <td class="py-2">{{ $row['type'] }}</td>
                            <td class="py-2 font-medium">{{ $row['total'] }}</td>
                        </tr>
                    @empty
                        <tr class="border-t">
                            <td class="py-3 text-gray-500" colspan="3">لا توجد بيانات خلال الفترة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

