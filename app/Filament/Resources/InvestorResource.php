<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Investor;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Forms\Components\Select;
use Forms\Components\TextInput;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvestorResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\InvestorResource\RelationManagers;

class InvestorResource extends Resource
{
    protected static ?string $model = Investor::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $pluralModelLabel = 'Data Investor'; //mengubah nama label
    protected static ?string $navigationLabel = 'Data Investor'; // mengubah nama label di menu
    protected static ?string $navigationGroup = 'Investor'; // membuat grup menu

    protected static ?string $recordTitleAttribute = 'investor';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Nomor Handphone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('bank')
                    ->label('Nama Bank')
                    ->options([
                        'BRI' => 'BRI',
                        'BCA' => 'BCA',
                        'BNI' => 'BNI',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('account_number')
                    ->label('Nomor Rekening')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_name')
                    ->label('Atas Nama Rekening')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    //->visible(fn () => auth()->user()->isAdmin()),
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Nomor Handphone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank')
                    ->label('Nama Bank')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'BRI' => 'success',
                        'BCA' => 'warning',
                        'BNI' => 'info',
                    }),
                Tables\Columns\TextColumn::make('account_number')
                    ->label('Nomor Rekening')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Atas Nama Rekening')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestors::route('/'),
            'create' => Pages\CreateInvestor::route('/create'),
            'view' => Pages\ViewInvestor::route('/{record}'),
            'edit' => Pages\EditInvestor::route('/{record}/edit'),
        ];
    }
}
