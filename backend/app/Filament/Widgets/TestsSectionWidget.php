<?php

namespace App\Filament\Widgets;

/**
 * عنوان قسم "الاختبارات" في لوحة المؤشرات.
 * EN: Section heading widget for the dashboard.
 */
class TestsSectionWidget extends SectionHeadingWidget
{
    protected static ?int $sort = 20;

    protected static ?string $title = 'الاختبارات';

    protected static ?string $subtitle = 'مستويات الطلاب واتجاه المتوسط عبر الأشهر';
}
