<?php

declare(strict_types=1);

namespace App\Task\Enums;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function canTransitionTo(self $target): bool
    {
        return match($this) {
            self::Backlog => $target === self::Todo,
            self::Todo => $target === self::InProgress,
            self::InProgress => in_array($target, [self::Todo, self::Done]),
            self::Done => $target === self::InProgress,
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::Backlog => 'Backlog',
            self::Todo => 'To Do',
            self::InProgress => 'In Progress',
            self::Done => 'Done',
        };
    }
}
