<?php

namespace App\Services;

use App\Models\Evaluator;

interface EvaluatorService
{
    public function addEvaluator(int $userId, int $periodId, string $type): Evaluator;
}
