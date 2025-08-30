<?php

namespace App\Dto;

/**
 * Lightweight read DTO for listing tasks.
 */
final readonly class TaskListItem
{
    public function __construct(
        public int $id,
        public string $name,
        public string $status,
        public int $priority,
        public ?string $assignedTo,
        public string $createdAt,
        public ?string $processedAt,
        public int $retryCount,
        public bool $done
    ) {}
}
