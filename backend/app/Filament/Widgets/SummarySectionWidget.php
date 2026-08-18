<?php

namespace App\Filament\Widgets;

/**
 * عنوان قسم "الملخّص" في لوحة المؤشرات.
 * EN: Section heading widget for the dashboard.
 */
class SummarySectionWidget extends SectionHeadingWidget
{
    protected static ?int $sort = 0;

    protected static ?string $title = 'الملخّص';

    protected static ?string $subtitle = 'المؤشرات الثلاثة تتبع الفلاتر أعلاه · أعداد المنظومة إجمالية لا تتأثر بها';
}
