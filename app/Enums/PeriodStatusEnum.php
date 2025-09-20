<?php

namespace App\Enums;

enum PeriodStatusEnum: string
{
    case STATUS_DISPATCH = 'DISPATCH';
    case STATUS_CLOSE = 'CLOSE';
    case STATUS_PRESELECTION = 'PRESELECTION';
    case STATUS_SELECTION = 'SELECTION';
}
