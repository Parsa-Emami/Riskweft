<?php

use App\Http\Controllers\Api\V1\ArchitectureRevisionController;
use Illuminate\Support\Facades\Route;

/*
 * Mirrors contracts/openapi.v1.yaml exactly: only the two Phase 0 routes
 * exist so far (commit + read a canonical revision). The other paths in the
 * frozen contract (evaluate/diff/merges/imports) are later phases and are
 * deliberately not stubbed out here - an unimplemented route that returns a
 * fake 202 would violate ACCEPTANCE_GATES_V7.md "Documentation contains no
 * capability claim ahead of implementation evidence" just as much as a
 * missing doc would.
 *
 * withRouting(api: ...) in bootstrap/app.php auto-prefixes every route here
 * with /api, so 'v1/...' below serves at /api/v1/... as the contract's
 * `servers: [{url: /api/v1}]` requires.
 */
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/projects/{project}/revisions', [ArchitectureRevisionController::class, 'store'])
        ->middleware(['idempotent', 'throttle:riskweft-writes'])
        ->name('riskweft.v1.revisions.store');

    Route::get('/revisions/{revision}', [ArchitectureRevisionController::class, 'show'])
        ->middleware('throttle:riskweft-reads')
        ->name('riskweft.v1.revisions.show');
});
