<?php

namespace App\Enums;

enum TaskType: string
{
    case Bug         = 'bug';
    case Feature     = 'feature';
    case Improvement = 'improvement';
    case Question    = 'question';

    public function label(): string
    {
        return match($this) {
            self::Bug         => 'Correctiva',
            self::Feature     => 'Evolutiva',
            self::Improvement => 'Preventiva',
            self::Question    => 'Soporte',
        };
    }
}
