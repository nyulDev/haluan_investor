<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use Filament\Actions;
use App\Models\Transaction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\TransactionResource;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return TransactionResource::beforeUpdate($data);
    }

    protected function afterSave(): void
    {
        // Update saldo untuk transaksi berikutnya
        $this->updateSubsequentBalances();
    }

    protected function updateSubsequentBalances(): void
    {
        $transaction = $this->record;
        $subsequentTransactions = Transaction::where('investor_id', $transaction->investor_id)
            ->where(function ($query) use ($transaction) {
                $query->where('date', '>', $transaction->date)
                    ->orWhere(function ($q) use ($transaction) {
                        $q->where('date', $transaction->date)
                            ->where('created_at', '>', $transaction->created_at);
                    });
            })
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $currentBalance = $transaction->balance;

        foreach ($subsequentTransactions as $subsequentTransaction) {
            if ($subsequentTransaction->mutation === 'kredit') {
                $currentBalance += $subsequentTransaction->amount;
            } else {
                $currentBalance -= $subsequentTransaction->amount;
            }

            $subsequentTransaction->update(['balance' => $currentBalance]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
