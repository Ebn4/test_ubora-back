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

    public function isSelectorEvaluator(): JsonResponse
    {
        $userId = auth()->user()->id;
        $evaluators = Evaluator::query()
            ->where('type', EvaluatorTypeEnum::EVALUATOR_SELECTION->value)
            ->where('user_id', $userId)
            ->whereHas('period', function ($query) {
                $query->where('year', Carbon::now()->year);
            })
            ->exists();
        return response()->json([
            "isSelectorEvaluator" => $evaluators
        ]);
    }

    public function isPreselectorEvaluator(): JsonResponse
    {
        $userId = auth()->user()->id;
        $evaluators = Evaluator::query()
            ->where('type', EvaluatorTypeEnum::EVALUATOR_PRESELECTION->value)
            ->where('user_id', $userId)
            ->whereHas('period', function ($query) {
                $query->where('year', Carbon::now()->year);
            })
            ->exists();
        return response()->json([
            "isPreselectorEvaluator" => $evaluators
        ]);
    }
}
