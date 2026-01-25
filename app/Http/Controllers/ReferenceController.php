<?php

namespace App\Http\Controllers;

use App\Actions\Reference\DeleteReferenceAction;
use App\Actions\Reference\ListReferenceAction;
use App\Actions\Reference\ShowReferenceAction;
use App\Actions\Reference\StoreReferenceAction;
use App\Actions\Reference\UpdateReferenceAction;
use App\Http\Requests\Reference\StoreReferenceRequest;
use App\Http\Requests\Reference\UpdateReferenceRequest;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    protected array $modelMap = [
        'nationalities' => \App\Models\Reference\Nationality::class,
        'occupations' => \App\Models\Reference\Occupation::class,
        'marital-statuses' => \App\Models\Reference\MaritalStatus::class,
        'patient-statuses' => \App\Models\Reference\PatientStatus::class,
    ];

    protected function getModelClass(string $type): string
    {
        if (!isset($this->modelMap[$type])) {
            abort(404, 'Reference type not found');
        }

        return $this->modelMap[$type];
    }

    public function index(Request $request, string $type, ListReferenceAction $action)
    {
        $modelClass = $this->getModelClass($type);

        return $action->execute($modelClass, $request->all());
    }

    public function show(string $type, int $id, ShowReferenceAction $action)
    {
        $modelClass = $this->getModelClass($type);

        return $action->execute($modelClass, $id);
    }

    public function store(StoreReferenceRequest $request, string $type, StoreReferenceAction $action)
    {
        $modelClass = $this->getModelClass($type);

        return $action->execute($modelClass, $request->validated());
    }

    public function update(UpdateReferenceRequest $request, string $type, int $id, UpdateReferenceAction $action)
    {
        $modelClass = $this->getModelClass($type);

        return $action->execute($modelClass, $id, $request->validated());
    }

    public function destroy(string $type, int $id, DeleteReferenceAction $action)
    {
        $modelClass = $this->getModelClass($type);

        return $action->execute($modelClass, $id);
    }
}
