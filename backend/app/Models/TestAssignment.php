<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * نموذج تعيين الاختبار (TestAssignment).
 *
 * Arabic: يمثل إسناد اختبار لطالب ضمن حلقة، مع حالة التعيين وتاريخ الإسناد ومن قام به.
 * ويرتبط بنتيجة واحدة (TestResult) عند اكتمال الاختبار.
 * EN: Test assignment model linking a test to a student in a halaqah, with status and one associated result.
 */
class TestAssignment extends Model
{
    use HasFactory;

    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABSENT_EXCUSED = 'absent_excused';
    public const STATUS_ABSENT_UNEXCUSED = 'absent_unexcused';

    protected $fillable = [
        'test_id',
        'student_id',
        'halaqah_id',
        'assigned_at',
        'assigned_by_user_id',
        'status',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * الاختبار المرتبط بالتعيين.
     * EN: Related test.
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * الطالب المعيّن له الاختبار.
     * EN: Related student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * الحلقة التي تم التعيين ضمنها.
     * EN: Related halaqah.
     */
    public function halaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class);
    }

    /**
     * المستخدم الذي قام بإنشاء التعيين (يدوياً أو عبر خدمة).
     * EN: User who assigned the test.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * نتيجة الاختبار للتعيين (إن وُجدت).
     * EN: Associated test result, if completed.
     */
    public function result(): HasOne
    {
        return $this->hasOne(TestResult::class, 'test_assignment_id');
    }
}

