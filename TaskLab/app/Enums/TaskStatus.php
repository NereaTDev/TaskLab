<?php

namespace App\Enums;

enum TaskStatus: string
{
    case New           = 'new';
    case InRefinement  = 'in_refinement';
    case ReadyForDev   = 'ready_for_dev';
    case InProgress    = 'in_progress';
    case Done          = 'done';
    case Blocked       = 'blocked';

    /** Statuses considered "active" for capacity/load calculations. */
    public static function activeStatuses(): array
    {
        return [
            self::New->value,
            self::InRefinement->value,
            self::ReadyForDev->value,
            self::InProgress->value,
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
