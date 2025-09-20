<?php

namespace App;

use App\Models\Evaluator;
use App\Services\EvaluatorService;

class EvaluatorServiceImpl implements EvaluatorService
{
    public function addEvaluator(int $userId, int $periodId, string $type): Evaluator
    {
        return Evaluator::query()->create([
            "user_id" => $userId,
            "period_id" => $periodId,
            "type" => $type
        ]);
    }
}
