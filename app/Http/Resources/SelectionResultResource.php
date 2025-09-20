<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SelectionResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'criteriaId' => $this->criteria_id ?? 0,
            'interviewId' => $this?->interview_id ?? 0,
            'evaluatorId' => $this?->evaluator_id ?? 0,
            'result' => $this?->result  ?? 0
        ];
    }
}
