<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CandidacyController;
use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\EvaluationFinaleController;
use App\Http\Controllers\EvaluatorController;
use App\Http\Controllers\HasAdminRoleController;
use App\Http\Controllers\LdapUserController;
use App\Http\Controllers\PeriodController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PreselectionController;
use App\Http\Controllers\UserController;

Route::post('login', [AuthenticationController::class, 'login']);
Route::apiResource('users', UserController::class)->only(['store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'show']);

    Route::get('has-admin-role', HasAdminRoleController::class);

    Route::post('candidate/selections', [CandidacyController::class, 'candidateSelections']);
    Route::get('rejeted_candidacies', [CandidacyController::class, 'rejetedCandidacies']);
    Route::get("evaluators/{id}/candidacies", [EvaluatorController::class, 'getEvaluatorCandidacies']);
    Route::apiResource('preselection', PreselectionController::class)->except(['index', 'show']);

    Route::get('getPreselectionsForDispatch/{dispatchId}', [PreselectionController::class, 'getPreselectionsForDispatch']);
    Route::get('/period/join/criteria', [CriteriaController::class, 'getCriteriaWithPeriodData']);
    Route::post('logout', [AuthenticationController::class, 'logout']);
    Route::get("CandidaciesDispatchEvaluator", [DispatchController::class, 'CandidaciesDispatchByEvaluator']);
    Route::get('/getDoc', [CandidacyController::class, "getDoc"]);
    Route::get('candidacies/selection-stats',[CandidacyController::class, 'getSelectionStats']);
    Route::apiResource('candidacies', CandidacyController::class)->only(['index', 'destroy', "show"]);
    Route::get('getYearsPeriod', [PeriodController::class, 'getYearsPeriod']);

    Route::get('periods/{id}/candidates/selection',[CandidacyController::class, 'getSelectionCandidates']);
    Route::get('candidates/{interviewId}/criterias/{criteriaId}/result', [CandidacyController::class, 'getCandidateSelectionResultByCriteria']);
    Route::get('candidates/{id}/evaluators', [CandidacyController::class, 'getCandidateEvaluators']);
    Route::get('candidates/interviews', [CandidacyController::class, 'getSelectedCandidates']);
    Route::get('periods/criteria/{id}', [PeriodController::class, 'getCriteriaPeriod']);
    Route::get('/candidates/{id}/interviews', [CandidacyController::class, "getCandidateInterview"]);
    Route::get('/candidates/{id}/has-selection', [CandidacyController::class, "candidateHasSelection"]);
    Route::apiResource('period', PeriodController::class);

    Route::get('evaluators/is-selector-evaluator', [EvaluatorController::class, 'isSelectorEvaluator']);
    Route::get('evaluators/is-preselector-evaluator', [EvaluatorController::class, 'isPreselectorEvaluator']);

    Route::middleware("admin")->group(function () {

        Route::apiResource('criteria', CriteriaController::class);
        Route::apiResource("evaluators", EvaluatorController::class);
        Route::apiResource('users', UserController::class)->except(['store']);

        Route::apiResource('users', UserController::class)->only(["index", "destroy"]);

        Route::post('upload-documents', [CandidacyController::class, 'uploadZipFile']);
        Route::get('upload-settings', [CandidacyController::class, 'getUploadSettings']);

        Route::get('periods/{period}/criteria', [PeriodController::class, 'getCriteriaPeriod']);
        Route::put('periods/{period}/status', [PeriodController::class, 'changePeriodStatus']);

        Route::get('preselection/periods/{period}/validate', [PreselectionController::class, 'canValidatePreselection']);
        Route::post('preselection/periods/{period}/validate', [PreselectionController::class, 'validatePreselection']);

        Route::get('evaluators/{period}/is-dispatched', [DispatchController::class, 'hasEvaluatorBeenDispatched']);
        Route::post("evaluators/{period}/dispatch", [DispatchController::class, 'dispatchPreselection']);
        Route::post("sendDispatchNotification", [DispatchController::class, 'sendDispatchNotification']);
        Route::get('users/ldap/{user}', LdapUserController::class);


        Route::post('/uploadCandidacies', [CandidacyController::class, "uploadCandidacies"]);
        Route::post('/uploadCandidaciesDocs', [CandidacyController::class, "uploadCandidaciesDocs"]);

        Route::get('periods/{id}/has-evaluators', [PeriodController::class, 'hasEvaluators']);
        Route::post('periods/attach-criteria/{id}', [CriteriaController::class, 'attachCriteriaToPeriod']);
    });
});
