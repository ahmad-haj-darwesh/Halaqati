<?php

namespace App\Filament\Widgets;

/**
 * عنوان قسم "الحضور والمتابعة اليومية" في لوحة المؤشرات.
 * EN: Section heading widget for the dashboard.
 */
class AttendanceSectionWidget extends SectionHeadingWidget
{
    protected static ?int $sort = 10;

    protected static ?string $title = 'الحضور والمتابعة اليومية';

    protected static ?string $subtitle = 'الحضور والغياب وأسباب التميّز والتقصير ضمن النطاق والفترة المحددين';
}
