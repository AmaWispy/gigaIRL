<?php

namespace App\Enums;

enum ItemType: string
{
    case Equipment = 'equipment';
    case Consumable = 'consumable';
    case Resource = 'resource';
    case TeleportStone = 'teleport_stone';
}
