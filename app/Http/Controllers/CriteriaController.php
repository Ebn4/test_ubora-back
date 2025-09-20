<?php

namespace App\Http\Controllers;

use App\Enums\EvaluatorTypeEnum;
use App\Enums\PeriodStatusEnum;
use App\Http\Requests\periodCriteriaAttache;
use App\Models\Criteria;
use App\Models\Evaluator;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class CriteriaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Criteria::query();

            if ($request->has('periodId') && $request->input('periodId') != null) {
                $periodId = $request->input('periodId');
                $query = $query->leftJoin('period_criteria', function ($join) use ($periodId) {
                    $join->on('criterias.id', '=', 'period_criteria.criteria_id')
                        ->where('period_criteria.period_id', '=', $periodId);
                })
                    ->where('period_id', $request->input('periodId'))
                    ->select(
                        'criterias.id',
                        'criterias.name',
                        'criterias.description',
                        'criterias.status',
                        'period_criteria.type',
                        'period_criteria.ponderation'
                    );

                if ($request->has('type')) {
                    $type = $request->input('type');
                    $query = $query->where('period_criteria.type', "{$type}");
                }
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query = $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }
            $perPage = $request->input('per_page', 5);
            $query = $query->paginate($perPage);

            return $query;
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'code' => 500,
                'description' => 'Erreur interne du serveur',
                'message' => 'Erreur interne du serveur'
            ]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        try {
            $criteria = Criteria::create(array_merge($request->only(['name', 'description']), ['status' => 'actif']));

            return response()->json([
                'success' => true,
                'message' => 'Critère créé avec succès.',
                'data' => $criteria
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du critère.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id)
    {
        $criteria = Criteria::find($id);

        if (!$criteria) {
            return 'Critère non trouvé.';
        }

        return $criteria;
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'description' => 'string|nullable|max:500'
        ]);

        $criteria = Criteria::find($id);

        if (!$criteria) {
            return response()->json([
                'success' => false,
                'message' => 'Critère non trouvé.'
            ], 404);
        }

        try {
            $criteria->update($request->only(['description']));

            return response()->json([
                'success' => true,
                'message' => 'Critère mis à jour avec succès.',
                'data' => $criteria
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du critère.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $criteria = Criteria::find($id);

        if (!$criteria) {
            return response()->json([
                'success' => false,
                'message' => 'Critère non trouvé.'
            ], 404);
        }

        try {
            if ($criteria->status === 'actif') {
                $criteria->status = 'inactif';
                $message = 'Critère désactivé avec succès.';
            } else {
                $criteria->status = 'actif';
                $message = 'Critère activé avec succès.';
            }
            $criteria->save();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $criteria
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut du critère.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function attachCriteriaToPeriod(PeriodCriteriaAttache $request, int $periodId): JsonResponse
    {
        try {
            $period = Period::findOrFail($periodId);
            if ($period->status != PeriodStatusEnum::STATUS_DISPATCH->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'exécuter cette action car le status n\'est plus en dispatch.',
                ], 500);
            } else {
                $period->criteria()->wherePivot('type', $request->type)->detach();
                $pivotData = [];

                foreach ($request->criteria as $critere) {
                    $pivotData[$critere['id']] = [
                        'type' => $request->type,
                        'ponderation' => $critere['ponderation'],
                    ];
                }

                $period->criteria()->syncWithoutDetaching($pivotData);

                return response()->json([
                    'success' => true,
                    'message' => 'Critères attachés avec succès.',
                    'data' => $period->load('criteria'),
                ]);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Période non trouvée.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'attachement des critères.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getCriteriaWithPeriodData(Request $request)
    {
        $request->validate([
            'period_id' => 'required|exists:periods,id',
            'dispatch_preselections_id' => 'nullable|exists:dispatch_preselections,id',
        ]);

        try {
            $periodId = $request->input('period_id');
            $dispatchPreselectionsId = $request->input('dispatch_preselections_id');

            $query = DB::table('criterias')
                ->leftJoin('period_criteria', function ($join) use ($periodId) {
                    $join->on('criterias.id', '=', 'period_criteria.criteria_id')
                        ->where('period_criteria.period_id', '=', $periodId);
                })
                ->where('criterias.status', '=', 'actif')
                ->select(
                    'criterias.id',
                    'criterias.name',
                    'criterias.description',
                    'criterias.status',
                    'period_criteria.type',
                    'period_criteria.ponderation',
                    'period_criteria.id as period_criteria_id',
                    DB::raw('NULL as valeur')
                );

            if ($dispatchPreselectionsId) {
                $dataEvaluateur = Evaluator::query()
                    ->where("user_id", auth()->user()->id)
                    ->where("type", EvaluatorTypeEnum::EVALUATOR_PRESELECTION->value)
                    ->where("period_id", $periodId)
                    ->firstOrFail();

                $evaluateurId = $dataEvaluateur->id;

                $query->leftJoin('preselections', function ($join) use ($dispatchPreselectionsId) {
                    $join->on('period_criteria.id', '=', 'preselections.period_criteria_id')
                        ->where('preselections.dispatch_preselections_id', '=', $dispatchPreselectionsId);
                })
                    ->leftJoin('dispatch_preselections', function ($join) {
                        $join->on('preselections.dispatch_preselections_id', '=', 'dispatch_preselections.id');
                    })
                    ->leftJoin('evaluators', function ($join) use ($evaluateurId) {
                        $join->on('dispatch_preselections.evaluator_id', '=', 'evaluators.id')
                            ->where('evaluators.id', '=', $evaluateurId);
                    })
                    ->selectRaw("CASE WHEN dispatch_preselections.evaluator_id IS NULL THEN NULL ELSE preselections.valeur END as valeur");
            }

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('criterias.name', 'LIKE', "%{$search}%")
                        ->orWhere('criterias.description', 'LIKE', "%{$search}%");
                });
            }

            $results = $query->get();

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'code' => 500,
                'description' => 'Erreur interne du serveur',
                'message' => 'Erreur interne du serveur'
            ]);
        }
    }
}
