<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'surname' => $this->surname,
            'name' => $this->name,
            'telephone' => $this->telephone,
            'sex' => $this->sex,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'multiple_birth' => $this->multiple_birth,
            'nationality_id' => $this->nationality_id,
            'nationality' => $this->whenLoaded('nationality', fn() => new ReferenceResource($this->nationality)),
            'marital_status_id' => $this->marital_status_id,
            'marital_status' => $this->whenLoaded('maritalStatus', fn() => new ReferenceResource($this->maritalStatus)),
            'occupation_id' => $this->occupation_id,
            'occupation' => $this->whenLoaded('occupation', fn() => new ReferenceResource($this->occupation)),
            'deceased' => $this->deceased,
            'deceased_at' => $this->deceased_at?->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
