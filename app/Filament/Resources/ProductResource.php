<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Catalog';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('productCategory')
            ->whereHas('tenant', fn (Builder $query) => $query->where('owner_user_id', auth()->id()));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('owner_user_id', auth()->id())
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('product_category_id')
                    ->label('Kategori')
                    ->relationship('productCategory', 'name')
                    ->rules(['uuid', 'exists:product_categories,id'])
                    ->helperText(fn (?Product $record) => $record && ! $record->product_category_id ? 'Kategori lama belum dipetakan. Pilih kategori master sebelum menyimpan.' : null)
                    ->required()
                    ->searchable(),
                Forms\Components\FileUpload::make('image_path')
                    ->label('Image')
                    ->image()
                    ->directory('products')
                    ->visibility('public')
                    ->maxSize(2048),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('original_price')
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('stock')
                    ->numeric()
                    ->minValue(0),
                Forms\Components\TextInput::make('unit')
                    ->maxLength(50),
                Forms\Components\TextInput::make('minimum_stock')
                    ->numeric()
                    ->minValue(0)
                    ->default(1),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\TextInput::make('weight_label')
                    ->maxLength(100),
                Forms\Components\Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('delivery_estimate')
                    ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('productCategory.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR', true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('original_price')
                    ->money('IDR', true)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
