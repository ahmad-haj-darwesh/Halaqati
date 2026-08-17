<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegionResource\Pages;
use App\Models\Region;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * مورد Filament لإدارة المناطق (Regions).
 *
 * Arabic: يعرّف نموذج إدخال المنطقة وجدول العرض، ويقيّد نطاق الرؤية حسب دور المستخدم
 * (SuperAdmin غير مقيّد، وAdmin يرى المناطق التي تحتوي مراكز يشرف عليها).
 * EN: Filament resource for managing regions (form/table) with role-based query scoping.
 */
class RegionResource extends Resource
{
    protected static ?string $model = Region::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'المناطق';
    protected static ?string $modelLabel = 'منطقة';
    protected static ?string $pluralModelLabel = 'المناطق';
    protected static ?int $navigationSort = 1;

    /**
     * نموذج إنشاء/تعديل المنطقة.
     * EN: Region create/edit form schema.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('اسم المنطقة')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label('الوصف')
                ->rows(3),
        ]);
    }

    /**
     * جدول عرض المناطق.
     * EN: Regions listing table configuration.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('centers_count')
                    ->label('عدد المراكز')
                    ->counts('centers')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('Y/m/d')
                    ->sortable(),
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
            'index'  => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit'   => Pages\EditRegion::route('/{record}/edit'),
        ];
    }

    /**
     * تقييد الاستعلام حسب المستخدم الحالي.
     *
     * Arabic: يضمن أن Admin يرى المناطق المرتبطة بمراكزه فقط عبر علاقة `centers`.
     * EN: Scopes regions query to regions that include centers administered by the user (non-SuperAdmin).
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('SuperAdmin')) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->whereHas('centers', fn ($q) => $q->where('admin_user_id', $user->id));
    }

    /**
     * السماح بإنشاء منطقة من لوحة الإدارة.
     * EN: Whether user can create regions.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['SuperAdmin', 'Admin']) ?? false;
    }
}
