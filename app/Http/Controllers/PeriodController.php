<?php

namespace App\Http\Controllers;

use App\Enums\EvaluatorTypeEnum;
use App\Enums\PeriodStatusEnum;
use App\Http\Requests\ChangePeriodStatusRequest;
use App\Http\Resources\CriteriaResource;
use App\Http\Resources\PeriodResource;
use App\Models\Evaluator;
use App\Models\Period;
use App\Models\StatusHistorique;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PeriodController extends Controller
{
    public function index(Request $request)
    {
        $query = Period::orderBy('year', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('year', 'LIKE', "%{$search}%");
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', 'LIKE', "%{$status}%");
        }

        $perPage = $request->input('per_page', 5);

        $paginated = $query->paginate($perPage);
        return PeriodResource::collection($paginated);
    }

    public function getYearsPeriod()
    {
        $query = Period::orderBy('year', 'desc')->get(['id', 'year']);

        $yearsWithPeriods = $query->map(function ($period) {
            $nextYear = $period->year + 1;

            return [
                'id' => $period->id,
                'year' => "{$period->year}-{$nextYear}",
            ];
        });

        return response()->json($yearsWithPeriods);
    }

    public function store(Request $request): JsonResponse
    {
        $user_id = $request->user()->id;
        $data = $request->validate([
            'year' => 'integer|unique:periods,year|nullable',
        ]);

        $yearNow = now()->year;
        $status = PeriodStatusEnum::STATUS_DISPATCH->value;

        if (isset($data['year']) && $data['year'] != null) {
            $yearNow = $data['year'];
        }

        try {
            $period = Period::create([
                'year' => $yearNow,
                'status' => $status
            ]);
            StatusHistorique::create([
                'period_id' => $period->id,
                'user_id' => $user_id,
                'status' => $period->status
            ]);
            return response()->json([
                'success' => true,
                'data' => $period
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de la période.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $period = Period::findOrFail($id);
            return new PeriodResource($period);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Période non trouvée.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function changePeriodStatus(ChangePeriodStatusRequest $request, int $id): JsonResponse
    {
        $period = Period::findOrFail($id);
        $currentStatus = $period->status;

        $newStatus = $request->post('status');

        $allowedTransitions = [
            PeriodStatusEnum::STATUS_DISPATCH->value => PeriodStatusEnum::STATUS_PRESELECTION->value,
            PeriodStatusEnum::STATUS_PRESELECTION->value => PeriodStatusEnum::STATUS_SELECTION->value,
            PeriodStatusEnum::STATUS_SELECTION->value => PeriodStatusEnum::STATUS_CLOSE->value,
        ];

        if (isset($allowedTransitions[$currentStatus]) && $allowedTransitions[$currentStatus] === $newStatus) {

            $period->update([
                "status" => $request->post('status')
            ]);

            return response()->json(['message' => 'Statut mis à jour avec succès.', 'period' => $period]);
        }


        return response()->json(['message' => 'Modification non autorisée pour ce statut.'], 403);
    }

    public function getCriteriaPeriod(Request $request, int $id)
    {
        try {

            $type = EvaluatorTypeEnum::EVALUATOR_PRESELECTION->value;
            if ($request->has('type') && $request->input('type') === 'SELECTION') {
                $type = EvaluatorTypeEnum::EVALUATOR_SELECTION->value;
            } elseif ($request->has('type') && $request->input('type') === 'PRESELECTION') {
                $type = EvaluatorTypeEnum::EVALUATOR_PRESELECTION->value;
            }

            $periods = Period::with(['criteria' => function ($query) use ($type) {
                $query->wherePivot('type', $type);
            }])->findOrFail($id);

            return CriteriaResource::collection($periods->criteria);
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'description' => 'Erreur interne du serveur',
                'message' => 'Erreur interne du serveur'
            ]);
        }
    }

    public function hasEvaluators(int $periodId): JsonResponse
    {
        $evaluators = Evaluator::query()->where('period_id', $periodId)->exists();
        return response()->json([
            "hasEvaluators" => $evaluators
        ]);
    }
}
