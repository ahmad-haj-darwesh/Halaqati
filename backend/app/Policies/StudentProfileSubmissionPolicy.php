<?php

namespace App\Policies;

use App\Models\StudentProfileSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * سياسة صلاحيات طلبات مراجعة ملفات الطلاب (StudentProfileSubmissionPolicy).
 *
 * Arabic: تتحكم في عرض الطلبات واتخاذ قرار (approve/reject) مع تقييد النطاق حسب المراكز المُدارة
 * وبشرط أن يكون الطلب قيد المراجعة (pending) قبل القبول/الرفض.
 * EN: Authorization policy for student profile submissions with managed-centers scoping and pending-only decisions.
 */
class StudentProfileSubmissionPolicy
{
    /**
     * صلاحية عرض قائمة الطلبات.
     * EN: Whether the user can view any submissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['SuperAdmin', 'Admin']);
    }

    /**
     * صلاحية عرض طلب محدد.
     * EN: Whether the user can view a specific submission.
     */
    public function view(User $user, StudentProfileSubmission $submission): bool
    {
        return $this->viewAny($user) && $this->submissionInScope($user, $submission);
    }

    /**
     * صلاحية قبول واعتماد الطلب.
     * EN: Whether the user can approve a submission.
     */
    public function approve(User $user, StudentProfileSubmission $submission): bool
    {
        if ($submission->status !== StudentProfileSubmission::STATUS_PENDING) {
            return false;
        }

        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->hasRole('Admin') && $this->submissionInScope($user, $submission);
    }

    /**
     * صلاحية رفض الطلب.
     * EN: Whether the user can reject a submission.
     */
    public function reject(User $user, StudentProfileSubmission $submission): bool
    {
        return $this->approve($user, $submission);
    }

    /**
     * التحقق أن الطلب ضمن نطاق المستخدم (مراكزه المُدارة).
     * EN: Checks whether the submission's student is enrolled in a halaqah within managed centers.
     */
    private function submissionInScope(User $user, StudentProfileSubmission $submission): bool
    {
        $centerIds = $user->managedCenters()->pluck('id');

        return $submission->student->enrollments()
            ->whereHas('halaqah', fn (Builder $q) => $q->whereIn('center_id', $centerIds))
            ->exists();
    }
}
