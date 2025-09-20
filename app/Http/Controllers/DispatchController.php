<?php

namespace App\Http\Controllers;

use App\Enums\EvaluatorTypeEnum;
use App\Enums\PeriodStatusEnum;
use App\Http\Requests\DispatchRequest;
use App\Http\Requests\CandidaciesDispatchEvaluator;
use App\Models\Candidacy;
use App\Models\DispatchPreselection;
use App\Models\Evaluator;
use App\Models\Period;
use App\Models\Preselection;
use App\Notifications\DispatchNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class DispatchController extends Controller
{

    public function hasEvaluatorBeenDispatched(int $periodId)
    {

        $candidacies = Candidacy::query()
            ->where("period_id", $periodId)
            ->whereHas("dispatch")
            ->exists();


        return response()->json([
            "isDispatch" => $candidacies
        ]);
    }

    public function dispatchPreselection(DispatchRequest $request): JsonResponse
    {

        $periodId = $request->post("periodId");

        $period = Period::query()->findOrFail($periodId);

        if ($period->status != PeriodStatusEnum::STATUS_DISPATCH->value) {
            throw new HttpResponseException(
                response: response()->json([
                    "error" => "Vous n'avez plus le droit de dispatcher : la présélection a déjà commencé."
                ])
            );
        }

        $candidacies = Candidacy::query()
            ->where("is_allowed", true)
            ->where("period_id", $periodId)
            ->get();

        $evaluators = Evaluator::query()
            ->where("period_id", $periodId)
            ->where("type", EvaluatorTypeEnum::EVALUATOR_PRESELECTION->value)
            ->get();

        $candidaciesIds = $candidacies
            ->pluck('id')
            ->toArray();

        $evaluatorsIds = $evaluators
            ->pluck('id')
            ->toArray();

        $evaluatorsDispatch = dispatch(
            candidatesIds: $candidaciesIds,
            evaluatedIds: $evaluatorsIds
        );

        foreach ($evaluatorsDispatch as $candidacyId => $evaluatorsIds) {
            $candidacy = Candidacy::query()->findOrFail($candidacyId);
            $candidacy->dispatch()->toggle($evaluatorsIds);
        }

        return response()->json([
            "message" => "Le dispatch de la présélection a été effectué avec succès."
        ]);
    }

    public function CandidaciesDispatchByEvaluator(Request $request)
    {
        $periodId = $request->input("periodId");
        $dataEvaluateur = Evaluator::query()
            ->where("user_id", auth()->user()->id)
            ->where("type", EvaluatorTypeEnum::EVALUATOR_PRESELECTION->value)
            ->where("period_id", $periodId)
            ->firstOrFail();
        if (!$dataEvaluateur) {
            throw new HttpResponseException(
                response: response()->json([
                    "error" => "Vous n'êtes pas autorisé à accéder à cette ressource."
                ])
            );
        }
        $evaluateurId = $dataEvaluateur?->id;

        $query = Candidacy::with(['dispatch' => function ($query) use ($evaluateurId) {
            $query->where('evaluator_id', $evaluateurId)->limit(1);
        }])
            ->where("period_id", $periodId)
            ->whereHas("dispatch", function ($q) use ($evaluateurId) {
                $q->where("evaluator_id", $evaluateurId);
            });

        if ($request->has('search') && $request->input('search') != null) {
            $search = $request->input('search');

            $query = $query->where(function ($q) use ($search) {
                $q->where('etn_nom', 'like', "%$search%")
                    ->orWhere('etn_prenom', 'like', "%$search%")
                    ->orWhere('etn_postnom', 'like', "%$search%")
                    ->orWhere('ville', 'like', "%$search%");
            });
        }

        if ($request->has('ville') && $request->input('ville') != null) {
            $ville = $request->input('ville');
            $query = $query->where('ville', 'LIKE', "%{$ville}%");
        }

        $candidaciesPreselection = DispatchPreselection::where('evaluator_id', $evaluateurId)
            ->has("preselections")
            ->count();

        $periodStatus = Period::query()
            ->where("id", $periodId)
            ->value("status");

        $count = $query->count();

        $perPage = $request->input('per_page', 5);
        $paginated = $query->paginate($perPage);

        try {
            $paginated->getCollection()->transform(function ($item) use ($candidaciesPreselection, $count, $evaluateurId, $periodStatus) {
                $statusCandidacy = DispatchPreselection::where('candidacy_id', $item->id)
                    ->where('evaluator_id', $evaluateurId)
                    ->has('preselections')
                    ->exists();

                $item->candidaciesPreselection = $candidaciesPreselection;
                $item->statusCandidacy = $statusCandidacy;
                $item->totalCandidats = $count;
                $item->periodStatus = $periodStatus;

                return $item;
            });

            return $paginated;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des périodes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function sendDispatchNotification()
    {
        $preselections = DispatchPreselection::with('evaluator.user')->get();

        $users = $preselections->map(function ($preselection) {
            return $preselection->evaluator?->user;
        })->filter()->unique('id');

        $urlFront = 'http://localhost:4200';

        Notification::send($users, new DispatchNotification($urlFront));
        return response()->json(['success' => true, 'message' => 'Notifications envoyées.']);
    }
}

function dispatch($candidatesIds, $evaluatedIds): array
{
    $result = [];
    $n = count($evaluatedIds);

    // Déterminer la taille du groupe (3 ou 2 selon la taille de e)
    $group_size = ($n >= 3) ? 3 : max(1, $n);

    foreach ($candidatesIds as $i => $ci) {
        $start = $i % $n;
        $group = [];

        for ($j = 0; $j < $group_size; $j++) {
            $index = ($start + $j) % $n;
            $group[] = $evaluatedIds[$index];
        }

        $result["$ci"] = $group;
    }

    return $result;
}
