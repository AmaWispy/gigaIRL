<?php

namespace App\Enums;

enum ExplorationActionType: string
{
    case Monster = 'monster';
    case Gather = 'gather';
    case Treasure = 'treasure';
    case ResourceCache = 'resource_cache';
    case RareMonster = 'rare_monster';
    case Boss = 'boss';
    case DungeonEntrance = 'dungeon_entrance';
}
