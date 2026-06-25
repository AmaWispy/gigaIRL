<?php

namespace App\Enums;

enum CombatStatus: string
{
    case Active = 'active';
    case Won = 'won';
    case Lost = 'lost';
    case Fled = 'fled';
}
