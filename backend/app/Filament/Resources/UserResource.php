<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

/**
 * مورد Filament لإدارة المستخدمين (Users).
 *
 * Arabic: يوفّر نموذجاً لإدارة بيانات المستخدم + تعيين الدور + تفعيل/تعطيل الحساب،
 * مع تقييد عرض/اختيار الأدوار بحيث لا يرى غير SuperAdmin أدوار SuperAdmin/Admin.
 * EN: Filament resource for managing users, roles, and activation with role visibility restrictions.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'المستخدمون';
    protected static ?string $modelLabel = 'مستخدم';
    protected static ?string $pluralModelLabel = 'المستخدمون';
    protected static ?int $navigationSort = 5;

    /**
     * نموذج إنشاء/تعديل المستخدم.
     *
     * Arabic: كلمة المرور تُحفظ فقط عند إدخالها (dehydrated)، وتكون إلزامية في الإنشاء فقط.
     * EN: User create/edit form schema; password is required on create and only persisted when filled.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create'),
            Select::make('roles')
                ->label('الدور')
                ->relationship('roles', 'name')
                ->options(function () {
                    $user = auth()->user();
                    if ($user->hasRole('SuperAdmin')) {
                        return Role::pluck('name', 'id');
                    }

                    return Role::whereNotIn('name', ['SuperAdmin', 'Admin'])
                        ->pluck('name', 'id');
                })
                ->required(),
            Toggle::make('is_active')->label('مفعّل')->default(true),
        ]);
    }

    /**
     * جدول عرض المستخدمين.
     *
     * Arabic: يعرض الاسم/البريد/الأدوار وحالة التفعيل وتاريخ الإنشاء مع فلتر حسب الدور.
     * EN: Users listing table with role filter and basic actions.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                TextColumn::make('email')->label('البريد')->searchable(),
                TextColumn::make('roles.name')->label('الدور')->badge(),
                IconColumn::make('is_active')->label('مفعّل')->boolean(),
                TextColumn::make('created_at')->label('تاريخ الإنشاء')->date('Y/m/d'),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('الدور')
                    ->relationship('roles', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    /**
     * صفحات المورد (List/Create/Edit).
     * EN: Resource pages routes.
     *
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب الدور.
     *
     * Arabic: SuperAdmin يرى كل المستخدمين. غير ذلك يتم إخفاء مستخدمي SuperAdmin/Admin
     * من جدول الإدارة لمنع العبث بصلاحيات عليا.
     * EN: Non-SuperAdmin users cannot see users with SuperAdmin/Admin roles.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('SuperAdmin')) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['SuperAdmin', 'Admin']));
    }

    /**
     * السماح بإنشاء مستخدم.
     * EN: Whether the current user can create users.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['SuperAdmin', 'Admin']) ?? false;
    }
}
