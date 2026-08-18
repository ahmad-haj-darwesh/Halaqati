<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * ويدجت عنوان قسم داخل لوحة المؤشرات.
 *
 * Arabic: لا تعرض بيانات — وظيفتها فصل الودجات إلى مجالات (الحضور، الاختبارات، الإشراف)
 * ليعرف المستخدم أين ينظر بدل أن تتداخل المجالات في شبكة واحدة.
 * EN: Non-data widget that groups dashboard widgets into labelled domain sections.
 */
abstract class SectionHeadingWidget extends Widget
{
    protected static string $view = 'filament.widgets.section-heading';

    protected int|string|array $columnSpan = 'full';

    /** عنوان القسم. */
    protected static ?string $title = null;

    /** سطر توضيحي اختياري تحت العنوان. */
    protected static ?string $subtitle = null;

    public function getTitle(): string
    {
        return static::$title ?? '';
    }

    public function getSubtitle(): ?string
    {
        return static::$subtitle;
    }
}
