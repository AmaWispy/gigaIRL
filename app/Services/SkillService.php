<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterSkill;
use App\Models\Skill;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SkillService
{
    public function maxEquipSlots(int $level): int
    {
        foreach (config('skills.slots') as $tier) {
            if ($level >= $tier['min_level'] && $level <= $tier['max_level']) {
                return $tier['count'];
            }
        }

        return 1;
    }

    public function getLearnedSkills(Character $character): Collection
    {
        return $character->characterSkills()
            ->with('skill')
            ->orderBy('equip_slot')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function getEquippedSkillKeys(Character $character): array
    {
        return $character->characterSkills()
            ->with('skill')
            ->where('is_equipped', true)
            ->orderBy('equip_slot')
            ->get()
            ->map(fn (CharacterSkill $row) => $row->skill->catalog_key)
            ->filter()
            ->values()
            ->all();
    }

    public function hasLearned(Character $character, Skill $skill): bool
    {
        return $character->characterSkills()
            ->where('skill_id', $skill->id)
            ->exists();
    }

    public function learn(Character $character, Skill $skill): CharacterSkill
    {
        if ($this->hasLearned($character, $skill)) {
            throw new InvalidArgumentException('Вы уже знаете этот приём.');
        }

        if ($character->level < $skill->min_learn_level) {
            throw new InvalidArgumentException("Нужен {$skill->min_learn_level} уровень персонажа.");
        }

        if ($character->money < $skill->teach_price) {
            throw new InvalidArgumentException('Недостаточно золота для обучения.');
        }

        return DB::transaction(function () use ($character, $skill) {
            $character->decrement('money', $skill->teach_price);

            return CharacterSkill::create([
                'character_id' => $character->id,
                'skill_id' => $skill->id,
                'level' => 1,
            ]);
        });
    }

    public function equip(Character $character, CharacterSkill $characterSkill): CharacterSkill
    {
        if ($characterSkill->character_id !== $character->id) {
            throw new InvalidArgumentException('Навык не принадлежит персонажу.');
        }

        $maxSlots = $this->maxEquipSlots($character->level);
        $equippedCount = $character->characterSkills()->where('is_equipped', true)->count();

        if ($characterSkill->is_equipped) {
            return $characterSkill;
        }

        if ($equippedCount >= $maxSlots) {
            throw new InvalidArgumentException("Можно экипировать не больше {$maxSlots} навыков.");
        }

        $usedSlots = $character->characterSkills()
            ->where('is_equipped', true)
            ->pluck('equip_slot')
            ->all();

        $slot = 1;
        while (in_array($slot, $usedSlots, true) && $slot <= $maxSlots) {
            $slot++;
        }

        $characterSkill->update([
            'is_equipped' => true,
            'equip_slot' => $slot,
        ]);

        return $characterSkill->fresh('skill');
    }

    public function unequip(Character $character, CharacterSkill $characterSkill): void
    {
        if ($characterSkill->character_id !== $character->id) {
            throw new InvalidArgumentException('Навык не принадлежит персонажу.');
        }

        $characterSkill->update([
            'is_equipped' => false,
            'equip_slot' => null,
        ]);
    }

    /**
     * @return list<array{
     *     id: int,
     *     skill_id: int,
     *     name: string,
     *     catalog_key: string|null,
     *     description: string|null,
     *     min_learn_level: int,
     *     teach_price: int,
     *     learned: bool,
     *     is_equipped: bool,
     *     equip_slot: int|null,
     *     can_learn: bool,
     * }>
     */
    public function getTrainerCatalog(Character $character): array
    {
        return Skill::query()
            ->whereNotNull('catalog_key')
            ->orderBy('min_learn_level')
            ->orderBy('name')
            ->get()
            ->map(function (Skill $skill) use ($character) {
                $characterSkill = $character->characterSkills()
                    ->where('skill_id', $skill->id)
                    ->first();

                return [
                    'id' => $skill->id,
                    'skill_id' => $skill->id,
                    'character_skill_id' => $characterSkill?->id,
                    'name' => $skill->name,
                    'catalog_key' => $skill->catalog_key,
                    'description' => $skill->description,
                    'min_learn_level' => $skill->min_learn_level,
                    'teach_price' => $skill->teach_price,
                    'learned' => $characterSkill !== null,
                    'is_equipped' => (bool) ($characterSkill?->is_equipped),
                    'equip_slot' => $characterSkill?->equip_slot,
                    'can_learn' => $characterSkill === null
                        && $character->level >= $skill->min_learn_level
                        && $character->money >= $skill->teach_price,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{
     *     id: int,
     *     skill_id: int,
     *     name: string,
     *     description: string|null,
     *     is_equipped: bool,
     *     equip_slot: int|null,
     * }>
     */
    public function formatSkillsForCharacter(Character $character): array
    {
        return $this->getLearnedSkills($character)
            ->map(fn (CharacterSkill $row) => [
                'id' => $row->id,
                'skill_id' => $row->skill_id,
                'name' => $row->skill->name,
                'description' => $row->skill->description,
                'is_equipped' => $row->is_equipped,
                'equip_slot' => $row->equip_slot,
            ])
            ->values()
            ->all();
    }
}
