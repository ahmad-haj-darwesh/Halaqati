<?php

namespace App\Exports;

use App\Models\AttendanceRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyAttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private int $month,
        private int $year,
        private ?int $centerId = null
    ) {}

    public function collection()
    {
        return AttendanceRecord::query()
            ->with(['student', 'halaqah.center', 'recordedBy'])
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->when($this->centerId, fn ($q) => $q->whereHas('halaqah', fn ($hq) => $hq->where('center_id', $this->centerId)))
            ->orderBy('date')
            ->get();
    }

    public function headings(): array
    {
        return ['التاريخ', 'اسم الطالب', 'الحلقة', 'المركز', 'المسجّل', 'الحالة', 'ملاحظات'];
    }

    /**
     * @param  AttendanceRecord  $record
     */
    public function map($record): array
    {
        $statusMap = [
            AttendanceRecord::STATUS_PRESENT => 'حاضر',
            AttendanceRecord::STATUS_EXCUSED => 'غياب مبرر',
            AttendanceRecord::STATUS_UNEXCUSED => 'غياب غير مبرر',
        ];

        return [
            $record->date,
            $record->student?->full_name ?? '—',
            $record->halaqah?->name ?? '—',
            $record->halaqah?->center?->name ?? '—',
            $record->recordedBy?->name ?? '—',
            $statusMap[$record->status] ?? $record->status,
            $record->notes ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
