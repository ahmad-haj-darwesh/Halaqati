<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * نموذج التقييم اليومي (DailyEvaluation).
 *
 * Arabic: يمثل تقييم الطالب في يوم محدد ضمن حلقة، مع تقييم عام (overall) وملاحظة عامة
 * وأسباب (reasons) عبر علاقة Many-to-Many. يحتوي منطقاً يحدد نافذة زمنية لتعديل المعلّم
 * (ساعة واحدة من وقت إنشاء السجل).
 * EN: Daily evaluation model with overall rating, general note, many-to-many reasons, and a time window for teacher edits.
 */
class DailyEvaluation extends Model
{
    use HasFactory;

    public const OVERALL_EXCELLENT = 'excellent';
    public const OVERALL_GOOD = 'good';
    public const OVERALL_NEEDS_IMPROVEMENT = 'needs_improvement';
    public const OVERALL_NONE = 'none';

    protected $fillable = [
        'halaqah_id',
        'student_id',
        'date',
        'overall',
        'recorded_by_user_id',
        'general_note',
        'client_recorded_at',
        'synced_at',
    ];

    protected $casts = [
        'client_recorded_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * الحلقة المرتبط بها التقييم.
     * EN: Related halaqah.
     */
    public function halaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class);
    }

    /**
     * الطالب المرتبط به التقييم.
     * EN: Related student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * المستخدم الذي سجل التقييم.
     * EN: Recorded-by user relation.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * أسباب التميّز/التقصير المرتبطة بالتقييم (Pivot: daily_evaluation_reason).
     * EN: Evaluation reasons linked via pivot table.
     */
    public function reasons(): BelongsToMany
    {
        return $this->belongsToMany(EvaluationReason::class, 'daily_evaluation_reason')
            ->withPivot([]);
    }

    /**
     * يُسمح للمعلّم بتعديل التقييم خلال نافذة محدودة من أول إدخال للسجل فقط.
     *
     * Arabic: مفيد لمنع التعديل المتأخر بعد اعتماد السجلات/التقارير. تُحتسب النافذة
     * من `client_recorded_at` (لحظة إدخال المعلّم على جهازه) عند توفره، لا من
     * `created_at` (لحظة وصول الصف للخادم) — وإلا لضاعت النافذة كاملةً على معلّم
     * سجّل دون إنترنت وتأخرت مزامنته ساعات.
     * EN: Limits teacher edits to a window measured from the client's recording time
     * when the row arrived via offline sync, falling back to server creation time.
     */
    public function canTeacherEditEvaluation(?Carbon $now = null): bool
    {
        $now = $now ?? now();
        $hours = max(0, (int) config('offline.evaluation_edit_hours', 1));

        return $now->lt($this->editWindowStartsAt()->copy()->addHours($hours));
    }

    /**
     * اللحظة التي تبدأ منها نافذة تعديل التقييم.
     * EN: The instant the teacher edit window starts counting from.
     */
    public function editWindowStartsAt(): Carbon
    {
        return $this->client_recorded_at ?? $this->created_at;
    }
}

