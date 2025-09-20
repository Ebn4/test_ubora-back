<?php

namespace App\Http\Resources;

use App\Models\Candidacy;
use App\Models\DispatchPreselection;
use Illuminate\Support\Facades\DB;
use App\Models\Interview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $id = $this->id;
        $countCandidaciesAllow = Candidacy::where('period_id', $id)
            ->where('is_allowed', true)
            ->count();

        $countCandidaciesPreselection = DispatchPreselection::whereHas('candidacy', function ($query) use ($id) {
            $query->where('period_id', $id);
        })
            ->distinct('candidacy_id')
            ->count();

        if ($countCandidaciesAllow == 0) {
            $progressionPreselection = 0;
        } else {
            $progressionPreselection = ceil(($countCandidaciesPreselection / $countCandidaciesAllow) * 100);
        }

        $this->loadCount('criteria');
        return [
            "id" => $this->id,
            "year" => $this->year . "-" . ($this->year + 1),
            "status" => $this->status,
            "evaluators_count" => DB::table('evaluators')
                ->where('period_id', $this->id)
                ->distinct('user_id')
                ->count('user_id'),
            "criteria_count" => $this->criteria_count,
            "candidats_count" => DB::table('candidats')
                ->where('is_allowed', true)
                ->where('period_id', $this->id)
                ->count('id'),
            "preselection_count" => Interview::whereHas('candidacy', function ($query) use ($id) {
                $query->where('period_id', $id);
            })->count(),
            "selection_count" => 0,
            "created_at" => "2025-07-11T10:42:29.000000Z",
            "updated_at" => "2025-07-11T10:42:29.000000Z",
            "progression_preselection" => $progressionPreselection . "%",
        ];
    }
}
