<?php
namespace App\Enum;

enum QueuePriority: int
{
    case LOW = 1;
    case NORMAL = 2;
    case HIGH = 3;
}
