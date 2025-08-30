<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Investor;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Transaction;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet'; //mengubah icon laben menu
    protected static ?string $pluralModelLabel = 'Kas Investor'; //mengubah nama label
    protected static ?string $navigationLabel = 'Kas Investor'; // mengubah nama label di menu
    protected static ?string $navigationGroup = 'Investor'; // membuat grup menu
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                 Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('investor_id')
                    ->label('Investor')
                    ->options(Investor::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $investor = Investor::find($state);
                        if ($investor) {
                            $lastTransaction = $investor->transactions()->latest('id')->first();
                            $set('balance', $lastTransaction ? $lastTransaction->balance : 0);
                        }
                    }),
                Forms\Components\Select::make('type')
                    ->options([
                        'debet' => 'Debet',
                        'kredit' => 'Kredit',
                    ])
                    ->required()
                    ->reactive(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        // Hitung ulang saldo ketika jumlah berubah
                        self::calculateBalance($set, $get);
                    }),
                Forms\Components\TextInput::make('balance')
                   ->numeric()
                    ->default(0)
                    ->readOnly()
                    ->prefix('Rp')
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('investor.name')
                    ->label('Investor')
                    //->visible(fn () => Auth::user()->isAdmin()) // Hanya visible untuk admin
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'kredit' => 'success',
                        'debet' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    //->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->money('IDR')
                    //->numeric()
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
                Tables\Filters\SelectFilter::make('investor')
                    
                    ->label('Investor')
                    ->relationship('investor','name'),
            ])
            ->actions([
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

     /**
     * Calculate balance based on form inputs
     */
    protected static function calculateBalance(callable $set, $get): void
    {
        $investorId = $get('investor_id');
        $date = $get('date');
        $type = $get('type');
        $amount = (float) $get('amount');

        if (!$investorId || !$date || !$type || !$amount) {
            $set('balance', 0);
            return;
        }

        // Cari saldo terakhir sebelum tanggal transaksi ini
        $lastTransaction = Transaction::where('investor_id', $investorId)
            ->where(function ($query) use ($date) {
                $query->where('date', '<', $date)
                    ->orWhere(function ($q) use ($date) {
                        $q->where('date', $date);
                    });
            })
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        $lastBalance = $lastTransaction ? $lastTransaction->balance : 0;

        // Hitung saldo baru
        if ($type === 'kredit') {
            $newBalance = $lastBalance + $amount;
        } else {
            $newBalance = $lastBalance - $amount;
        }

        $set('balance', $newBalance);
    }
}
