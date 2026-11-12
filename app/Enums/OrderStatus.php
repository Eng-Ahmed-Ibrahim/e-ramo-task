<?php
namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [
                self::CONFIRMED,
                self::CANCELLED,
            ],

            self::CONFIRMED => [
                self::PROCESSING,
                self::CANCELLED,
            ],

            self::PROCESSING => [
                self::COMPLETED,
                self::CANCELLED,
            ],

            self::COMPLETED,
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}