<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\CancelOrder;
use App\Actions\ChangeOrderStatus;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Order $record
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return "Заказ №{$this->record->number}";
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->statusAction(OrderStatus::Processing, 'Собирается', Heroicon::OutlinedArchiveBox, 'info'),
            $this->statusAction(OrderStatus::Shipped, 'Передан в доставку', Heroicon::OutlinedTruck, 'info'),
            $this->statusAction(OrderStatus::Completed, 'Выполнен', Heroicon::OutlinedCheckCircle, 'success'),
            Action::make('cancel')
                ->label('Отменить')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn () => $this->record->status->canTransitionTo(OrderStatus::Cancelled))
                ->requiresConfirmation()
                ->modalHeading('Отменить заказ?')
                ->modalDescription(fn () => $this->record->paid_at
                    ? 'Товар вернётся на склад. Заказ оплачен — оформите возврат денег в личном кабинете ЮKassa.'
                    : 'Товар вернётся на склад, покупатель получит письмо.')
                ->schema([
                    TextInput::make('reason')
                        ->label('Причина (увидит покупатель)')
                        ->default('Отменён магазином')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(fn (array $data, CancelOrder $cancelOrder) => $this->run(
                    fn () => $cancelOrder->handle($this->record, $data['reason']),
                    'Заказ отменён, товар возвращён на склад',
                )),
        ];
    }

    private function statusAction(OrderStatus $status, string $label, Heroicon $icon, string $color): Action
    {
        return Action::make($status->value)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn () => $this->record->status->canTransitionTo($status))
            ->requiresConfirmation()
            ->modalHeading("Статус «{$status->getLabel()}»?")
            ->modalDescription('Покупатель получит письмо о смене статуса.')
            ->action(fn (ChangeOrderStatus $changeStatus) => $this->run(
                fn () => $changeStatus->handle($this->record, $status),
                "Статус: {$status->getLabel()}",
            ));
    }

    private function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->title($success)->success()->send();
        } catch (InvalidOrderTransitionException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }

        $this->record->refresh();
    }
}
