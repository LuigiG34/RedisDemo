<?php

namespace App\Message;

final class TaskProcessMessage
{
    public function __construct(private int $taskId) {}

    public function getTaskId(): int { return $this->taskId; }
}
