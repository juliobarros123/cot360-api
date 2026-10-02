<?php
// ============================================================
// routes/api.php — OMNI INSURANCE V2 (completo)
// ============================================================

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PDFController;

// ---- Auth ----
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('sso');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('sso');

// ---- Endpoints públicos para proposta (formulário do cliente) ----
Route::prefix('quo/quotation-proposals')->group(function () {
    Route::get('/track/{token}', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'trackView']);
    Route::get('/status/{token}', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'validateToken']);
    Route::post('/complete/{token}', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'markAsCompleted']);
});

// ---- Endpoints públicos para simuladores rápidos do portal ----
Route::prefix('public/simulations')->group(function () {
    Route::get('/work-accident/caes', [\App\Http\Controllers\Api\PublicPortal\WorkAccidentSimulationController::class, 'caes']);
    Route::post('/work-accident', [\App\Http\Controllers\Api\PublicPortal\WorkAccidentSimulationController::class, 'simulate']);
    Route::get('/motor/options', [\App\Http\Controllers\Api\PublicPortal\MotorSimulationController::class, 'options']);
    Route::post('/motor', [\App\Http\Controllers\Api\PublicPortal\MotorSimulationController::class, 'simulate']);
    Route::post('/health-family', [\App\Http\Controllers\Api\PublicPortal\HealthFamilySimulationController::class, 'simulate']);
});

// ---- Endpoints públicos de PDF (sem token) ----
Route::prefix('pdf')->name('pdf.')->group(function () {
    Route::post('generate/quotation', [PDFController::class, 'generate_quatation'])->name('generate.quotation');
    Route::get('quotations/{quotationId}/reprint/{version}', [PDFController::class, 'reprintQuotation'])->name('quotation.reprint');
    Route::get('renewal-terms-proposal/{id}', [PDFController::class, 'renewal_terms_proposal'])->name('renewal.terms.proposal');
    Route::get('generate/auto-proposal', [PDFController::class, 'generate_auto_proposal_pdf'])->name('generate.auto.proposal');
});

// ---- Endpoints públicos de PDF de proposta (sem token) ----
Route::prefix('quo/quotation-proposals')->group(function () {
    Route::get('/pdf/print/{quotation_proposal_id}', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'print']);
    Route::get('/pdf/print/{quotation_proposal_id}/{document}', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'printDocument']);
    Route::get('/by-quotation-version/{quotation_version_id}/pdf', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'printByQuotationVersion']);
});

// ---- Endpoints públicos de PDF de apólice (sem token) ----
Route::get('pol/policies/{id}/pdf', [\App\Http\Controllers\Api\POL\POLPolicyPdfController::class, 'print'])
    ->name('pol.policies.print-pdf.public');
// ---- API protegida ----
Route::middleware('sso')->group(function () {
    // ========================================================
    // CFG — CONFIGURAÇÕES
    // ========================================================
    Route::prefix('cfg')->name('cfg.')->group(function () {

        // Ramos
        Route::apiResource('insurance-branches', \App\Http\Controllers\Api\CFG\CFGInsuranceBranchesController::class);
        Route::patch('insurance-branches/{id}/toggle', [\App\Http\Controllers\Api\CFG\CFGInsuranceBranchesController::class, 'toggle'])
            ->name('insurance-branches.toggle');

        // Templates
        Route::apiResource('templates', \App\Http\Controllers\Api\CFG\CFGTemplatesController::class);
        Route::post('templates/{id}/coverages', [\App\Http\Controllers\Api\CFG\CFGTemplatesController::class, 'syncCoverages'])
            ->name('templates.sync-coverages');

        // Coberturas
        Route::apiResource('coverages', \App\Http\Controllers\Api\CFG\CFGCoveragesController::class);

        // Franquias
        Route::apiResource('deductibles', \App\Http\Controllers\Api\CFG\CFGDeductiblesController::class);

        // Condições especiais
        Route::apiResource('special-conditions', \App\Http\Controllers\Api\CFG\CFGSpecialConditionsController::class);

        // Tarifas
        Route::apiResource('rates/automobile', \App\Http\Controllers\Api\CFG\CFGRatesAutomobileController::class);
        Route::apiResource('rates/health', \App\Http\Controllers\Api\CFG\CFGRatesHealthController::class);
        Route::apiResource('rates/cae', \App\Http\Controllers\Api\CFG\CFGRatesCaeController::class);
        Route::apiResource('rates/occupants', \App\Http\Controllers\Api\CFG\CFGRatesOccupantsController::class);
        Route::apiResource('rates/rc-options', \App\Http\Controllers\Api\CFG\CFGRatesRcOptionsController::class);
        Route::apiResource('rates/school-accident', \App\Http\Controllers\Api\CFG\CFGRatesSchoolAccidentController::class);
        // Template-Cobertura pivot
        Route::apiResource('template-coverage', \App\Http\Controllers\Api\CFG\CFGTemplateCoverageController::class);
    });

    // ========================================================
    // ENT — ENTIDADES
    // ========================================================
    Route::prefix('ent')->name('ent.')->group(function () {

        // Entidades (principal)o 
        Route::apiResource('entities', \App\Http\Controllers\Api\ENT\ENTEntitiesController::class);
        Route::patch('entities/{id}/kyc', [\App\Http\Controllers\Api\ENT\ENTEntitiesController::class, 'updateKyc'])
            ->name('entities.update-kyc');

        // Sub-perfis (nested sob entidade)
        Route::prefix('entities/{entityId}')->group(function () {
            Route::apiResource('personal', \App\Http\Controllers\Api\ENT\ENTPersonalEntitiesController::class)
                ->only(['index', 'store', 'show', 'update']);
            Route::apiResource('corporate', \App\Http\Controllers\Api\ENT\ENTCorporateEntitiesController::class)
                ->only(['index', 'store', 'show', 'update']);
            Route::apiResource('addresses', \App\Http\Controllers\Api\ENT\ENTAddressesController::class);
            Route::apiResource('contacts', \App\Http\Controllers\Api\ENT\ENTContactsController::class);
            Route::apiResource('roles', \App\Http\Controllers\Api\ENT\ENTEntityRolesController::class);
            Route::apiResource('compliance-documents', \App\Http\Controllers\Api\ENT\ENTComplianceDocumentsController::class);
        });

        // IBANs de papéis
        Route::apiResource('entity-role-ibans', \App\Http\Controllers\Api\ENT\ENTEntityRoleIbansController::class);

        // Comissões por ramo
        Route::apiResource('agent-branch-commissions', \App\Http\Controllers\Api\ENT\ENTAgentBranchCommissionsController::class);

        // Canais de venda
        Route::apiResource('sales-channels', \App\Http\Controllers\Api\ENT\ENTSalesChannelsController::class);

        // Tipos de papel (referência)
        Route::apiResource('role-types', \App\Http\Controllers\Api\ENT\ENTRoleTypesController::class);

        // Sub-tipos de papel
        Route::apiResource('role-subtypes', \App\Http\Controllers\Api\ENT\ENTRoleSubtypesController::class);
    });

    // ========================================================
    // OBJ — OBJECTOS SEGURADOS (NOVO v2)
    // ========================================================
    Route::prefix('obj')->name('obj.')->group(function () {

        // Objectos (principal — cria objecto + sub-tipo inline)
        Route::apiResource('objects', \App\Http\Controllers\Api\OBJ\OBJObjectsController::class);

        // Sub-tipos (acesso directo para updates)
        Route::apiResource('vehicles', \App\Http\Controllers\Api\OBJ\OBJVehiclesController::class);
        Route::apiResource('persons', \App\Http\Controllers\Api\OBJ\OBJPersonsController::class);
        Route::apiResource('properties', \App\Http\Controllers\Api\OBJ\OBJPropertiesController::class);
        Route::apiResource('salary-aggregates', \App\Http\Controllers\Api\OBJ\OBJSalaryAggregatesController::class);

        // Sub-capitais (Opção B)
        Route::apiResource('object-capitals', \App\Http\Controllers\Api\OBJ\OBJObjectCapitalsController::class);

        // Consulta reutilização: cotações e apólices onde o objecto aparece
        Route::get('objects/{id}/usages', [\App\Http\Controllers\Api\OBJ\OBJObjectsController::class, 'usages'])
            ->name('objects.usages');
    });

    // ========================================================
    // QUO — COTAÇÕES
    // ========================================================
    Route::prefix('quo')->name('quo.')->group(function () {

        // Cotações (POST cria tudo transaccionalmente)
        Route::apiResource('quotations', \App\Http\Controllers\Api\QUO\QUOQuotationsController::class);

        // Acções de estado
        Route::patch('quotations/{id}/accept', [\App\Http\Controllers\Api\QUO\QUOQuotationsController::class, 'accept'])
            ->name('quotations.accept');
        Route::patch('quotations/{id}/reject', [\App\Http\Controllers\Api\QUO\QUOQuotationsController::class, 'reject'])
            ->name('quotations.reject');
        Route::post('quotations/{id}/versions', [\App\Http\Controllers\Api\QUO\QUOQuotationsController::class, 'newVersion'])
            ->name('quotations.new-version');
        Route::get('quotations/{id}/discount-status', [\App\Http\Controllers\Api\QUO\QUOQuotationsController::class, 'discountStatus'])
            ->name('quotations.discount-status');

        // Versões
        Route::apiResource('quotation-versions', \App\Http\Controllers\Api\QUO\QUOQuotationVersionsController::class);
        Route::get('quotation-versions/{id}/export-excel', [\App\Http\Controllers\Api\QuotationExcelController::class, 'exportFrotaSemCondutor'])
            ->name('quotation-versions.export-excel');

        // Riscos por objecto
        Route::apiResource('object-risks', \App\Http\Controllers\Api\QUO\QUOObjectRiskController::class);

        // Prémios
        Route::apiResource('premiums', \App\Http\Controllers\Api\QUO\QUOPremiumsController::class);

        // Condições especiais
        Route::apiResource('special-conditions', \App\Http\Controllers\Api\QUO\QUOSpecialConditionsController::class);

        // Ajustes
        Route::apiResource('insurance-adjustments', \App\Http\Controllers\Api\QUO\QUOInsuranceAdjustmentsController::class);

        Route::prefix('quotation-proposals')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'index']);
            Route::get('/list-simple', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'listSimple']);
            Route::post('/send', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'sendProposal']);
            Route::get('/quotation/{quotation_version_id}', [\App\Http\Controllers\Api\QUO\QUOQuotationProposalController::class, 'getByQuotation']);
        });
    });

    // ========================================================
    // POL — APÓLICES
    // ========================================================
    Route::prefix('pol')->name('pol.')->group(function () {

        // Apólices (POST = converte cotação → apólice transaccionalmente)
        Route::apiResource('policies', \App\Http\Controllers\Api\POL\POLPoliciesController::class);

        // Acções específicas
        Route::post('policies/{id}/cancel', [\App\Http\Controllers\Api\POL\POLPoliciesController::class, 'cancel'])
            ->name('policies.cancel');
        Route::post('policies/{id}/amendment', [\App\Http\Controllers\Api\POL\POLPoliciesController::class, 'amendment'])
            ->name('policies.amendment');
        // Versões
        Route::apiResource('policy-versions', \App\Http\Controllers\Api\POL\POLPolicyVersionsController::class)
            ->only(['index', 'show']);

        // Riscos por objecto
        Route::apiResource('object-risks', \App\Http\Controllers\Api\POL\POLObjectRiskController::class);

        // Movimentos
        Route::apiResource('movements', \App\Http\Controllers\Api\POL\POLMovementsController::class);
        Route::patch('movements/{id}/approve', [\App\Http\Controllers\Api\POL\POLMovementsController::class, 'approve'])
            ->name('movements.approve');

        // Detalhes de movimentos
        Route::apiResource('movement-details', \App\Http\Controllers\Api\POL\POLMovementDetailsController::class);

        // Condições especiais da apólice
        Route::apiResource('special-conditions', \App\Http\Controllers\Api\POL\POLSpecialConditionsController::class);

        // Co-seguro
        Route::apiResource('coinsurance-shares', \App\Http\Controllers\Api\POL\POLCoinsuranceSharesController::class);

        // Resseguro
        Route::apiResource('reinsurance-cessions', \App\Http\Controllers\Api\POL\POLReinsuranceCessionsController::class);
    });

    // ========================================================
    // MSG — MENSAGENS
    // ========================================================
    Route::prefix('msg')->name('msg.')->group(function () {
        Route::apiResource('messages', \App\Http\Controllers\Api\MSG\MSGMessagesController::class);
        Route::apiResource('templates', \App\Http\Controllers\Api\MSG\MSGTemplatesController::class);
    });
});
