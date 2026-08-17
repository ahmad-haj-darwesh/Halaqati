<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج درجة الزيارة الإشرافية (SupervisoryVisitScore).
 *
 * Arabic: يمثل درجة محور محدد ضمن زيارة إشرافية، ويرتبط بمحور القالب (Rubric Item)
 * مع إمكانية إضافة ملاحظة.
 * EN: Score entry for a specific rubric item within a supervisory visit, with optional note.
 */
class SupervisoryVisitScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'supervisory_visit_id',
        'supervision_rubric_item_id',
        'score',
        'note',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    /**
     * الزيارة الإشرافية الأم.
     * EN: Parent supervisory visit.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(SupervisoryVisit::class, 'supervisory_visit_id');
    }

    /**
     * محور القالب (Rubric Item) الذي تم تقييمه.
     * EN: Related rubric item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(SupervisionRubricItem::class, 'supervision_rubric_item_id');
    }
}

