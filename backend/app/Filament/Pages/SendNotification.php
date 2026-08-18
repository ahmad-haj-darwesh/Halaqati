<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Center;
use App\Models\User;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * صفحة إرسال إشعار للمستخدمين من لوحة الإدارة.
 *
 * Arabic: تسمح باختيار المستلمين (الجميع / حسب الدور / مستخدمون محددون) ثم إرسال
 * إشعار عبر `NotificationService`. الإشعار يُخزَّن دائماً داخل التطبيق
 * (`AppNotification`) بينما إشعار الهاتف (FCM) يعتمد على وجود توكن لدى المستخدم
 * وعلى بيئة الإنتاج — لذا يُفصل العدّان في رسالة النتيجة بدل الإيهام بنجاح كامل.
 *
 * النطاق: SuperAdmin يرسل للجميع، ومدير المركز يقتصر على معلّمي مراكزه ومديريها.
 *
 * EN: Filament page to broadcast a notification to users, scoped by role/centers.
 */
class SendNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'إرسال إشعار';

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'النظام';

    protected static ?string $title = 'إرسال إشعار للمستخدمين';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.send-notification';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /**
     * الأدوار التي تستخدم تطبيق الجوال فعلياً.
     *
     * Arabic: إرسال إشعار لدور لا يملك تطبيقاً يخزّنه بلا فائدة، لذا تقتصر
     * الخيارات على أدوار التطبيق.
     *
     * @return array<string, string>
     */
    public static function mobileRoles(): array
    {
        return [
            'Teacher' => 'المعلّمون',
            'Examiner' => 'المختبِرون',
            'CenterSupervisor' => 'مشرفو الحلقات',
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['SuperAdmin', 'Admin']) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'target' => 'role',
            'role' => 'Teacher',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('المستلمون')
                    ->schema([
                        Radio::make('target')
                            ->label('إرسال إلى')
                            ->options([
                                'role' => 'كل من يحمل دوراً معيّناً',
                                'users' => 'مستخدمون محدَّدون',
                                'all' => 'جميع المستخدمين النشطين',
                            ])
                            ->default('role')
                            ->required()
                            ->live(),

                        Select::make('role')
                            ->label('الدور')
                            ->options(self::mobileRoles())
                            ->native(false)
                            ->required(fn (Get $get): bool => $get('target') === 'role')
                            ->visible(fn (Get $get): bool => $get('target') === 'role')
                            ->live(),

                        Select::make('user_ids')
                            ->label('المستخدمون')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => $this->scopedUsersQuery()
                                ->orderBy('name')
                                ->limit(500)
                                ->pluck('name', 'id')
                                ->all())
                            ->required(fn (Get $get): bool => $get('target') === 'users')
                            ->visible(fn (Get $get): bool => $get('target') === 'users')
                            ->live(),

                        Placeholder::make('recipients_count')
                            ->label('عدد المستلمين')
                            ->content(fn (): HtmlString => $this->recipientsSummary()),
                    ]),

                Section::make('محتوى الإشعار')
                    ->schema([
                        TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('مثال: تذكير بتسجيل الحضور'),

                        Textarea::make('body')
                            ->label('النص')
                            ->required()
                            ->maxLength(500)
                            ->rows(4)
                            ->placeholder('نص الإشعار كما سيظهر للمستخدم'),
                    ]),
            ]);
    }

    /**
     * قاعدة المستخدمين المسموح للمستخدم الحالي مراسلتهم.
     *
     * Arabic: SuperAdmin بلا قيد؛ أما مدير المركز فيقتصر على معلّمي حلقات مراكزه
     * وعلى مديري تلك المراكز، منعاً للإرسال خارج نطاق صلاحيته.
     * EN: Base query of users the current admin may message.
     */
    protected function scopedUsersQuery(): Builder
    {
        $me = auth()->user();

        $query = User::query()->where('is_active', true);

        if ($me === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($me->hasRole('SuperAdmin')) {
            return $query;
        }

        $centerIds = $me->managedCenters()->pluck('id');

        if ($centerIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        $centerAdminIds = Center::query()
            ->whereIn('id', $centerIds)
            ->pluck('admin_user_id')
            ->filter()
            ->all();

        return $query->where(function (Builder $sub) use ($centerIds, $centerAdminIds): void {
            $sub->whereHas(
                'teacherProfile.halaqah',
                fn (Builder $h) => $h->whereIn('center_id', $centerIds)
            )->orWhereIn('id', $centerAdminIds);
        });
    }

    /**
     * استعلام المستلمين بعد تطبيق اختيار النموذج.
     * EN: Recipients query after applying the selected targeting mode.
     */
    protected function recipientsQuery(): Builder
    {
        $data = $this->data ?? [];
        $query = $this->scopedUsersQuery();

        return match ($data['target'] ?? null) {
            'all' => $query,
            'role' => filled($data['role'] ?? null)
                ? $query->role($data['role'])
                : $query->whereRaw('1 = 0'),
            'users' => filled($data['user_ids'] ?? null)
                ? $query->whereIn('id', $data['user_ids'])
                : $query->whereRaw('1 = 0'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * ملخّص المستلمين: الإجمالي وكم منهم يملك توكن إشعارات.
     *
     * Arabic: التمييز مهم — من لا يملك توكناً يستلم الإشعار داخل التطبيق فقط
     * ولا يصله تنبيه على الهاتف.
     * EN: Total recipients vs. those reachable by push.
     */
    protected function recipientsSummary(): HtmlString
    {
        $total = (clone $this->recipientsQuery())->count();

        if ($total === 0) {
            return new HtmlString('<span class="text-warning-600">لا يوجد مستلمون مطابقون</span>');
        }

        $withToken = (clone $this->recipientsQuery())->whereNotNull('fcm_token')->count();

        return new HtmlString(
            "<strong>{$total}</strong> مستخدم — منهم <strong>{$withToken}</strong> يملك تطبيقاً مسجَّلاً لاستقبال إشعار الهاتف."
        );
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('إرسال الإشعار')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('تأكيد الإرسال')
                ->modalDescription(fn (): string => sprintf(
                    'سيُرسل الإشعار إلى %d مستخدم. لا يمكن التراجع بعد الإرسال.',
                    (clone $this->recipientsQuery())->count()
                ))
                ->modalSubmitActionLabel('إرسال')
                ->modalCancelActionLabel('إلغاء')
                ->action(fn () => $this->send()),
        ];
    }

    /**
     * تنفيذ الإرسال بعد التحقق من النموذج.
     * EN: Validates the form and dispatches the notification.
     */
    public function send(): void
    {
        $data = $this->form->getState();

        $recipients = $this->recipientsQuery()->get();

        if ($recipients->isEmpty()) {
            Notification::make()
                ->title('لم يُرسل شيء')
                ->body('لا يوجد مستخدمون مطابقون للاختيار الحالي.')
                ->warning()
                ->send();

            return;
        }

        $result = app(NotificationService::class)->sendToUsers(
            $recipients->all(),
            $data['title'],
            $data['body'],
            ['type' => 'admin_broadcast'],
        );

        Notification::make()
            ->title('تم الإرسال')
            ->body(sprintf(
                'وصل الإشعار داخل التطبيق إلى %d مستخدم. إشعارات الهاتف: %d نجحت، %d لم تُرسل (بلا توكن أو تعذّر الإرسال).',
                $recipients->count(),
                $result['sent'],
                $result['failed'],
            ))
            ->success()
            ->persistent()
            ->send();

        $this->form->fill([
            'target' => $data['target'],
            'role' => $data['role'] ?? null,
        ]);
    }
}
