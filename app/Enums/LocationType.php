<?php

namespace App\Enums;

enum LocationType: string
{
    case City = 'city';
    case Village = 'village';
    case Field = 'field';
    case Forest = 'forest';
    case River = 'river';
    case Dungeon = 'dungeon';
}
