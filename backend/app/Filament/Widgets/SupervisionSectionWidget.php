<?php

namespace App\Filament\Widgets;

/**
 * عنوان قسم "الإشراف التربوي" في لوحة المؤشرات.
 * EN: Section heading widget for the dashboard.
 */
class SupervisionSectionWidget extends SectionHeadingWidget
{
    protected static ?int $sort = 30;

    protected static ?string $title = 'الإشراف التربوي';

    protected static ?string $subtitle = 'نتائج الزيارات الميدانية على المعلمين والمراكز';
}
