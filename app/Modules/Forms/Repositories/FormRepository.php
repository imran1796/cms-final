<?php

namespace App\Modules\Forms\Repositories;

use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Repositories\Interfaces\FormRepositoryInterface;

final class FormRepository implements FormRepositoryInterface
{
    public function list(int $spaceId): array
    {
        return Form::query()->where('space_id', $spaceId)->orderByDesc('id')->get()->all();
    }

    public function find(int $spaceId, int $id): ?Form
    {
        return Form::query()->where('space_id', $spaceId)->where('id', $id)->first();
    }

    public function findByHandle(int $spaceId, string $handle): ?Form
    {
        return Form::query()->where('space_id', $spaceId)->where('handle', $handle)->first();
    }

    public function create(array $data): Form
    {
        return Form::create($data);
    }

    public function update(Form $form, array $data): Form
    {
        $form->fill($data);
        $form->save();
        return $form;
    }

    public function delete(Form $form): void
    {
        $form->delete();
    }
}
