<div class="fi-submission-diff">
    <div class="fi-submission-diff-head">
        <span>الحقل</span>
        <span>القيمة الحالية</span>
        <span>القيمة المقترحة</span>
    </div>

    @foreach ($rows as $row)
        <div @class(['fi-submission-diff-row', 'fi-submission-diff-changed' => $row['changed']])>
            <span class="fi-submission-diff-label">{{ $row['label'] }}</span>
            <span class="fi-submission-diff-current">{{ $row['current'] }}</span>
            <span class="fi-submission-diff-proposed">
                {{ $row['proposed'] }}
                @if ($row['changed'])
                    <em>معدّل</em>
                @endif
            </span>
        </div>
    @endforeach

    @if (collect($rows)->every(fn ($r) => ! $r['changed']))
        <p class="fi-submission-diff-empty">لا توجد اختلافات بين القيم الحالية والمقترحة.</p>
    @endif
</div>
