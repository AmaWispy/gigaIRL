<?php

namespace App\Http\Controllers\Admin;

use App\Admin\AdminLookupService;
use App\Admin\AdminResourceRegistry;
use App\Admin\DungeonConfigPresenter;
use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    public function __construct(
        private AdminLookupService $lookup,
        private DungeonConfigPresenter $dungeonConfig,
    ) {}

    public function index(Request $request, string $resource): Response
    {
        $config = AdminResourceRegistry::get($resource);
        $model = $config['model'];
        $sort = $config['default_sort'] ?? 'id';

        $records = $model::query()
            ->when($request->search, function ($query, string $search) use ($config) {
                $firstTextColumn = collect($config['fields'] ?? [])
                    ->firstWhere('type', 'text')['name'] ?? 'id';
                $query->where($firstTextColumn, 'like', "%{$search}%");
            })
            ->orderBy($sort)
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Model $record) => $this->lookup->serializeRecord($config, $record));

        return Inertia::render('Admin/Resources/Index', [
            'resourceKey' => $resource,
            'resource' => $this->resourceMeta($config),
            'records' => $records,
            'filters' => ['search' => $request->search],
        ]);
    }

    public function show(string $resource, int $record): Response
    {
        $config = AdminResourceRegistry::get($resource);
        $model = $config['model'];
        $item = $model::query()->findOrFail($record);

        $payload = [
            'resourceKey' => $resource,
            'resource' => $this->resourceMeta($config),
            'record' => $this->lookup->serializeFull($config, $item),
        ];

        if ($resource === 'dungeons' && $item instanceof Dungeon) {
            $payload['dungeonConfig'] = $this->dungeonConfig->present($item);
        }

        return Inertia::render('Admin/Resources/Show', $payload);
    }

    public function create(string $resource): Response
    {
        $config = AdminResourceRegistry::get($resource);

        if (! ($config['creatable'] ?? false)) {
            abort(403);
        }

        return Inertia::render('Admin/Resources/Edit', [
            'resourceKey' => $resource,
            'resource' => $this->resourceMeta($config),
            'record' => $this->defaultValues($config),
            'isCreate' => true,
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $config = AdminResourceRegistry::get($resource);

        if (! ($config['creatable'] ?? false)) {
            abort(403);
        }

        $data = $this->validatedData($request, $config);
        $model = $config['model'];
        $item = $model::query()->create($data);

        return redirect()
            ->route('admin.resources.show', [$resource, $item->getKey()])
            ->with('success', 'Запись создана');
    }

    public function edit(string $resource, int $record): Response
    {
        $config = AdminResourceRegistry::get($resource);

        if (! ($config['editable'] ?? false)) {
            abort(403);
        }

        $model = $config['model'];
        $item = $model::query()->findOrFail($record);

        return Inertia::render('Admin/Resources/Edit', [
            'resourceKey' => $resource,
            'resource' => $this->resourceMeta($config),
            'record' => $this->lookup->serializeFull($config, $item),
            'isCreate' => false,
        ]);
    }

    public function update(Request $request, string $resource, int $record): RedirectResponse
    {
        $config = AdminResourceRegistry::get($resource);

        if (! ($config['editable'] ?? false)) {
            abort(403);
        }

        $model = $config['model'];
        $item = $model::query()->findOrFail($record);
        $item->update($this->validatedData($request, $config));

        return redirect()
            ->route('admin.resources.show', [$resource, $item->getKey()])
            ->with('success', 'Запись обновлена');
    }

    public function destroy(string $resource, int $record): RedirectResponse
    {
        $config = AdminResourceRegistry::get($resource);

        if (! ($config['editable'] ?? false)) {
            abort(403);
        }

        $model = $config['model'];
        $model::query()->findOrFail($record)->delete();

        return redirect()
            ->route('admin.resources.index', $resource)
            ->with('success', 'Запись удалена');
    }

    /** @param  array<string, mixed>  $config */
    private function resourceMeta(array $config): array
    {
        return [
            'label' => $config['label'],
            'group' => $config['group'],
            'columns' => $config['columns'],
            'fields' => $config['fields'],
            'editable' => $config['editable'] ?? false,
            'creatable' => $config['creatable'] ?? false,
        ];
    }

    /** @param  array<string, mixed>  $config */
    private function defaultValues(array $config): array
    {
        $defaults = [];

        foreach ($config['fields'] as $field) {
            $defaults[$field['name']] = match ($field['type']) {
                'boolean' => false,
                'number' => null,
                'keyvalue', 'loot_table', 'ingredients', 'json' => [],
                default => '',
            };
        }

        return $defaults;
    }

    /** @param  array<string, mixed>  $config */
    private function validatedData(Request $request, array $config): array
    {
        $rules = [];

        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $rule = [];

            if ($field['required'] ?? false) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            $rule[] = match ($field['type']) {
                'number', 'item_select', 'location_select', 'monster_select', 'dungeon_select',
                'skill_select', 'user_select', 'character_select' => 'integer',
                'boolean' => 'boolean',
                'keyvalue', 'ingredients', 'json', 'loot_table' => 'array',
                default => 'string',
            };

            $rules[$name] = $rule;

            if ($field['type'] === 'loot_table') {
                $rules["{$name}.*.item_id"] = ['required', 'integer'];
                $rules["{$name}.*.chance"] = ['required', 'numeric'];
                $rules["{$name}.*.quantity"] = ['required', 'integer'];
                $rules["{$name}.*.equipment_quality"] = ['nullable', 'string'];
            }

            if ($field['type'] === 'ingredients') {
                $rules["{$name}.*.item_id"] = ['required', 'integer'];
                $rules["{$name}.*.quantity"] = ['required', 'integer'];
            }
        }

        $validated = $request->validate($rules);

        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            if (($field['type'] ?? '') === 'boolean') {
                $validated[$name] = (bool) ($validated[$name] ?? false);
            }
            if (in_array($field['type'] ?? '', ['item_select', 'location_select', 'monster_select', 'dungeon_select', 'skill_select', 'user_select', 'character_select'], true)) {
                $validated[$name] = $validated[$name] !== null && $validated[$name] !== '' ? (int) $validated[$name] : null;
            }
        }

        return $validated;
    }
}
