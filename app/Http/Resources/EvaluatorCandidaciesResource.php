<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluatorCandidaciesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->etn_nom,
            "firstname" => $this->etn_prenom,
            "lastname" => $this->etn_postnom,
            "email" => $this->etn_email,
            "city" => $this->ville,
            "state" => $this->province,
            "nationality" => $this->nationalite,
            "gender" => $this->sexe,
        ];
    }
}
