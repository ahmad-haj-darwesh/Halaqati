<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج مرفق الزيارة الإشرافية (SupervisoryVisitAttachment).
 *
 * Arabic: يمثل ملفاً مرفقاً بزيارة إشرافية (مسار التخزين، الاسم الأصلي، نوع الملف، الحجم).
 * EN: Attachment model for a supervisory visit (storage path, original name, mime type, size).
 */
class SupervisoryVisitAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'supervisory_visit_id',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    /**
     * الزيارة الإشرافية الأم.
     * EN: Parent supervisory visit relation.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(SupervisoryVisit::class, 'supervisory_visit_id');
    }
}

