<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use Filament\Actions;
use App\Models\Transaction;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\TransactionResource;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function afterCreate(): void
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
            if ($subsequentTransaction->type === 'kredit') {
                $currentBalance += $subsequentTransaction->amount;
            } else {
                $currentBalance -= $subsequentTransaction->amount;
            }

            $subsequentTransaction->update(['balance' => $currentBalance]);
        }
    }
}
