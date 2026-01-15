<?php

namespace App\Http\Controllers;

use App\Enums\EvaluatorTypeEnum;
use App\Enums\PeriodStatusEnum;
use App\Http\Requests\EvaluatorRequest;
use App\Http\Resources\CandidacyResource;
use App\Http\Resources\EvaluatorCandidaciesResource;
use App\Http\Resources\EvaluatorRessource;
use App\Models\Evaluator;
use App\Models\Period;
use App\Models\User;
use App\Services\EvaluatorService;
use App\Services\UserLdapService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EvaluatorController extends Controller
{
    public function __construct(
        private EvaluatorService $evaluatorService,
        private UserService      $userService,
        private UserLdapService  $userLdapService
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {

        $perPage = 10;

        if ($request->has('perPage')) {
            $perPage = $request->input('perPage');
        }

        $evaluators = Evaluator::query()
            ->with(["user", "period"]);

        if ($request->has('periodId')) {
            $periodId = $request->input('periodId');
            $evaluators = $evaluators->where('period_id', $periodId);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $evaluators = $evaluators->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        if ($request->has('type') && $request->input('type') != "") {
            $type = $request->input('type');
            $evaluators = $evaluators->where('type', "=", $type);
        }

        $evaluators = $evaluators->paginate($perPage);

        return EvaluatorRessource::collection($evaluators);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EvaluatorRequest $request): void
    {
        try {
            $type = $request->type;
            $period = Period::query()->findOrFail($request->periodId);

            if ($period->status == PeriodStatusEnum::STATUS_CLOSE->value) {
                throw new \Exception("Vous n'avez plus le droit d'ajouter un evaluateur pour cette periode.");
            }

            $ldapUser = $this->userLdapService->findUserByCuid($request->cuid);

            $exists = User::where('email', $ldapUser->email)->exists();
            if ($exists) {
                $user = User::where('email', $ldapUser->email)->first();
            } else {
                $user = $this->userService->create($ldapUser->email, $ldapUser->cuid, "evaluator", $ldapUser->name);
            }

            $evaluator = Evaluator::query()
                ->where('user_id', $user->id)
                ->where('period_id', $request->periodId)
                ->where(function ($query) use ($type, $request) {
                    if ($type === 'SELECTION') {
                        $query->where('type', 'SELECTION')
                            ->where('period_id', $request->periodId);
                    } elseif ($type === 'PRESELECTION') {
                        $query->where('type', 'PRESELECTION')
                            ->where('period_id', $request->periodId);
                    }
                })
                ->first();

            if ($evaluator) {
                throw new \Exception("L'utilisateur est déjà enregistré en tant qu'évaluateur pour l'épreuve de {$type}.");
            }


            if ($period->status != PeriodStatusEnum::STATUS_DISPATCH->value && $type == EvaluatorTypeEnum::EVALUATOR_PRESELECTION->value) {
                throw new \Exception("Vous n'avez plus le droit d'ajouter un evaluateur de PRESELECTION pour cette periode.");
            }

            $this->evaluatorService->addEvaluator($user->id, $request->periodId, $type);
        } catch (\Exception $e) {
            throw  new HttpResponseException(
                response: response()->json(['errors' => $e->getMessage()], 400)
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): EvaluatorRessource
    {
        $evaluator = Evaluator::query()
            ->with(["candidacies"])
            ->findOrFail($id);
        return new EvaluatorRessource($evaluator);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $evaluator = Evaluator::query()
                ->findOrFail($id);

            $period = Period::query()->findOrFail($evaluator->period_id);

            if ($period->status != PeriodStatusEnum::STATUS_DISPATCH->value) {
                throw new \Exception("Vous  n'avez pas le droit d'effacer cet evaluateur car le status de la periode est PRESELECTION.");
            }

            $evaluator->delete();
        } catch (\Exception $e) {
            throw  new HttpResponseException(
                response: response()->json(['errors' => $e->getMessage()], 400)
            );
        }
    }

    public function evaluatorCandidacy(int $id): AnonymousResourceCollection
    {
        $candidacies = Evaluator::query()
            ->with("dispatch")
            ->findOrFail($id);

        return EvaluatorCandidaciesResource::collection($candidacies->dispatch);
    }

    public function getEvaluatorCandidacies(int $evaluatorId): AnonymousResourceCollection
    {
        $candidacies = Evaluator::query()
            ->with("candidacies")
            ->findOrFail($evaluatorId)
            ->candidacies;

        return CandidacyResource::collection($candidacies);
    }

     public function isSelectorEvaluator(Request $request): JsonResponse
    {
        try {
            $userId = auth()->user()->id;

            // Étape 1: Si periodId fourni, l'utiliser
            if ($request->has('periodId') && $request->periodId) {
                $evaluators = Evaluator::query()
                    ->where('type', EvaluatorTypeEnum::EVALUATOR_SELECTION->value)
                    ->where('user_id', $userId)
                    ->where('period_id', $request->periodId)
                    ->exists();

                return response()->json([
                    "success" => true,
                    "isSelectorEvaluator" => $evaluators,
                    "periodId" => $request->periodId,
                    "usedProvidedPeriod" => true
                ]);
            }

            // Étape 2: Trouver TOUTES les périodes où l'utilisateur est évaluateur de sélection
            $evaluatorPeriods = Evaluator::query()
                ->where('type', EvaluatorTypeEnum::EVALUATOR_SELECTION->value)
                ->where('user_id', $userId)
                ->with('period')
                ->get()
                ->pluck('period')
                ->filter()
                ->sortByDesc('year')
                ->values();

            if ($evaluatorPeriods->isNotEmpty()) {
                // Prendre la période la plus récente
                $latestPeriod = $evaluatorPeriods->first();

                return response()->json([
                    "success" => true,
                    "isSelectorEvaluator" => true,
                    "periodId" => $latestPeriod->id,
                    "periodName" => $latestPeriod->year,
                    "periodYear" => $latestPeriod->year,
                    "availablePeriods" => $evaluatorPeriods->map(function($period) {
                        return [
                            'id' => $period->id,
                            'year' => $period->year,
                            'name' => $period->name,
                            'status' => $period->status
                        ];
                    }),
                    "usedLatestPeriod" => true,
                    "message" => "Évaluateur de sélection trouvé pour la période " . $latestPeriod->year
                ]);
            }

            // Étape 3: Aucune période trouvée
            return response()->json([
                "success" => true,
                "isSelectorEvaluator" => false,
                "availablePeriods" => [],
                "message" => "Vous n'êtes pas évaluateur de sélection"
            ]);

        } catch (\Throwable $th) {
            \Log::error('Erreur isSelectorEvaluator', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                "success" => false,
                "isSelectorEvaluator" => false,
                "message" => "Une erreur est survenue lors de la vérification"
            ], 500);
        }
    }

    /**
     * Vérifie si l'utilisateur est évaluateur de présélection
     * Accepte un periodId optionnel, sinon trouve la période appropriée
     */
    public function isPreselectorEvaluator(Request $request): JsonResponse
    {
        try {
            $userId = auth()->user()->id;

            // Étape 1: Si periodId fourni, l'utiliser
            if ($request->has('periodId') && $request->periodId) {
                $evaluators = Evaluator::query()
                    ->where('type', EvaluatorTypeEnum::EVALUATOR_PRESELECTION->value)
                    ->where('user_id', $userId)
                    ->where('period_id', $request->periodId)
                    ->exists();

                return response()->json([
                    "success" => true,
                    "isPreselectorEvaluator" => $evaluators,
                    "periodId" => $request->periodId,
                    "usedProvidedPeriod" => true
                ]);
            }

            // Étape 2: Trouver TOUTES les périodes où l'utilisateur est évaluateur de présélection
            $evaluatorPeriods = Evaluator::query()
                ->where('type', EvaluatorTypeEnum::EVALUATOR_PRESELECTION->value)
                ->where('user_id', $userId)
                ->with('period')
                ->get()
                ->pluck('period')
                ->filter()
                ->sortByDesc('year')
                ->values();

            if ($evaluatorPeriods->isNotEmpty()) {
                // Prendre la période la plus récente
                $latestPeriod = $evaluatorPeriods->first();

                return response()->json([
                    "success" => true,
                    "isPreselectorEvaluator" => true,
                    "periodId" => $latestPeriod->id,
                    "periodName" => $latestPeriod->year,
                    "periodYear" => $latestPeriod->year,
                    "availablePeriods" => $evaluatorPeriods->map(function($period) {
                        return [
                            'id' => $period->id,
                            'year' => $period->year,
                            'name' => $period->name,
                            'status' => $period->status
                        ];
                    }),
                    "usedLatestPeriod" => true,
                    "message" => "Évaluateur de présélection trouvé pour la période " . $latestPeriod->year
                ]);
            }

            // Étape 3: Aucune période trouvée
            return response()->json([
                "success" => true,
                "isPreselectorEvaluator" => false,
                "availablePeriods" => [],
                "message" => "Vous n'êtes pas évaluateur de présélection"
            ]);

        } catch (\Throwable $th) {
            \Log::error('Erreur isPreselectorEvaluator', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                "success" => false,
                "isPreselectorEvaluator" => false,
                "message" => "Une erreur est survenue lors de la vérification"
            ], 500);
        }
    }

    /**
     * Récupère toutes les périodes où l'utilisateur est évaluateur
     * (Pour le sélecteur de période dans le frontend)
     */
    public function getEvaluatorPeriods(Request $request): JsonResponse
    {
        try {
            $userId = auth()->user()->id;

            $periods = Period::whereHas('evaluators', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('year', 'desc')
            ->get(['id', 'year', 'name', 'status', 'created_at']);

            return response()->json([
                "success" => true,
                "periods" => $periods,
                "count" => $periods->count()
            ]);

        } catch (\Throwable $th) {
            \Log::error('Erreur getEvaluatorPeriods', [
                'error' => $th->getMessage()
            ]);

            return response()->json([
                "success" => false,
                "periods" => [],
                "message" => "Une erreur est survenue"
            ], 500);
        }
    }
}
