<?php

namespace App\Exports;

use App\Models\TestResult;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TestResultsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private ?int $testId = null) {}

    public function collection()
    {
        return TestResult::query()
            ->with(['assignment.student', 'assignment.halaqah', 'examiner'])
            ->when($this->testId, fn ($q) => $q->whereHas('assignment', fn ($a) => $a->where('test_id', $this->testId)))
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return ['اسم الطالب', 'الحلقة', 'المختبر', 'السورة المختبَرة', 'درجة الحفظ', 'درجة التجويد', 'درجة المراجعة', 'المجموع', 'المستوى', 'تاريخ الاختبار'];
    }

    /**
     * @param  TestResult  $result
     */
    public function map($result): array
    {
        $total = (int) $result->total_score;
        $level = match (true) {
            $total >= 90 => 'ممتاز',
            $total >= 75 => 'جيد جداً',
            $total >= 60 => 'جيد',
            $total >= 50 => 'مقبول',
            default => 'ضعيف',
        };

        return [
            $result->assignment?->student?->full_name ?? '—',
            $result->assignment?->halaqah?->name ?? '—',
            $result->examiner?->name ?? '—',
            $result->tested_surah ?? '—',
            $result->memorization_score,
            $result->tajweed_score,
            $result->review_score,
            $total,
            $level,
            $result->tested_at?->format('Y-m-d') ?? $result->created_at?->format('Y-m-d') ?? '—',
        ];
    }
}
