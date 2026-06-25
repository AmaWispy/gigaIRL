<?php

namespace App\Admin;

use App\Models\Character;
use App\Models\Dungeon;
use App\Models\Item;
use App\Models\Location;
use App\Models\Monster;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdminLookupService
{
    /** @return array<string, mixed> */
    public function options(): array
    {
        return [
            'items' => Item::query()->orderBy('name')->pluck('name', 'id'),
            'locations' => Location::query()->orderBy('name')->pluck('name', 'id'),
            'monsters' => Monster::query()->orderBy('name')->pluck('name', 'id'),
            'dungeons' => Dungeon::query()->orderBy('name')->pluck('name', 'id'),
            'skills' => Skill::query()->orderBy('name')->pluck('name', 'id'),
            'users' => User::query()->orderBy('nickname')->pluck('nickname', 'id'),
            'characters' => Character::query()
                ->with('user:id,nickname')
                ->get()
                ->mapWithKeys(fn (Character $c) => [$c->id => $c->user?->nickname ?? "Персонаж #{$c->id}"]),
        ];
    }

    /** @param  array<string, mixed>  $resource */
    public function serializeRecord(array $resource, Model $record): array
    {
        $data = $record->toArray();
        $columns = $resource['columns'] ?? array_keys($data);

        $row = ['id' => $record->getKey()];

        foreach ($columns as $column) {
            $value = data_get($data, $column);
            $row[$column] = $this->formatValue($column, $value, $record);
        }

        return $row;
    }

    /** @param  array<string, mixed>  $resource */
    public function serializeFull(array $resource, Model $record): array
    {
        $data = $record->toArray();

        foreach ($resource['fields'] as $field) {
            $name = $field['name'];
            if (! array_key_exists($name, $data)) {
                continue;
            }
            $data[$name] = $this->formatFieldValue($field, $data[$name]);
        }

        return $data;
    }

    private function formatValue(string $column, mixed $value, Model $record): mixed
    {
        if (is_bool($value)) {
            return $value ? 'да' : 'нет';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (str_ends_with($column, '_id') && $value) {
            return $this->resolveForeignLabel($column, (int) $value, $record);
        }

        return $value;
    }

    /** @param  array<string, mixed>  $field */
    private function formatFieldValue(array $field, mixed $value): mixed
    {
        if ($field['type'] === 'boolean') {
            return (bool) $value;
        }

        if (in_array($field['type'], ['keyvalue', 'loot_table', 'ingredients', 'json'], true) && is_string($value)) {
            return json_decode($value, true) ?? [];
        }

        return $value;
    }

    private function resolveForeignLabel(string $column, int $id, Model $record): string
    {
        $relation = match ($column) {
            'user_id' => 'user',
            'character_id' => 'character',
            'item_id' => 'item',
            'location_id', 'from_location_id', 'to_location_id', 'current_location_id', 'parent_id' => 'location',
            'monster_id' => 'monster',
            'dungeon_id' => 'dungeon',
            'skill_id' => 'skill',
            'result_item_id' => 'resultItem',
            'achievement_template_id' => 'template',
            default => null,
        };

        if ($relation && method_exists($record, $relation)) {
            $related = $record->{$relation};
            if ($related) {
                return $related->name ?? $related->title ?? $related->nickname ?? "#{$id}";
            }
        }

        return (string) $id;
    }
}
