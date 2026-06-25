<?php

namespace App\Enums;

enum AchievementFrequency: string
{
    case None = 'none';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Once = 'once';
}
