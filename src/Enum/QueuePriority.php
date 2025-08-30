<?php
namespace App\Enum;

enum QueuePriority: int
{
    case LOW = 1;
    case NORMAL = 2;
    case HIGH = 3;

    /** Map enum -> transport service name declared in messenger.yaml */
    public function transport(): string
    {
        return match ($this) {
            self::HIGH   => 'async_high',
            self::LOW    => 'async_low',
            self::NORMAL => 'async',
        };
    }

    /** Convert any DB int to an enum */
    public static function fromInt(int $value): self
    {
        return match (true) {
            $value >= 3 => self::HIGH,
            $value <= 1 => self::LOW,
            default     => self::NORMAL,
        };
    }
}
