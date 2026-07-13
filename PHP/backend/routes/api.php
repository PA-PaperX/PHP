<?php
use FastRoute\RouteCollector;
use App\Controllers\IssueController;
use App\Controllers\InventoryController;

return FastRoute\simpleDispatcher(function(RouteCollector $r) {
    $r->addGroup('/api', function (RouteCollector $r) {
        
        // --- Issues (Pure REST) ---
        $r->addRoute('GET', '/issues', [
            'handler' => [IssueController::class, 'index'],
            'middleware' => ['auth']
        ]);
        
        $r->addRoute('GET', '/issues/{id:\d+}', [
            'handler' => [IssueController::class, 'show'],
            'middleware' => ['auth']
        ]);
        
        $r->addRoute('POST', '/issues', [
            'handler' => [IssueController::class, 'store'],
            'middleware' => ['auth']
        ]);
        
        $r->addRoute('POST', '/issues/{id:\d+}', [
            'handler' => [IssueController::class, 'update'],
            'middleware' => ['auth']
        ]);
        
        $r->addRoute('DELETE', '/issues/{id:\d+}', [
            'handler' => [IssueController::class, 'destroy'],
            'middleware' => ['auth']
        ]);
        
        // --- Legacy fallback paths mapped to new architecture ---
        $r->addRoute('POST', '/issues/create', [
            'handler' => [IssueController::class, 'store'],
            'middleware' => ['auth']
        ]);
        
        $r->addRoute('POST', '/issues/upload_slip', [
            'handler' => [IssueController::class, 'uploadSlip'],
            'middleware' => ['auth']
        ]);
        
        $r->addRoute('GET', '/issues/index', [
            'handler' => [IssueController::class, 'index'],
            'middleware' => ['auth']
        ]);
        
        $r->addRoute('GET', '/inventory/index', [
            'handler' => [InventoryController::class, 'index']
        ]);
        
        $r->addRoute('POST', '/inventory/create', [
            'handler' => [InventoryController::class, 'store'],
            'middleware' => ['auth']
        ]);
        
        $r->addRoute('POST', '/inventory/update', [
            'handler' => [InventoryController::class, 'update'],
            'middleware' => ['auth']
        ]);
        
        $r->addRoute('POST', '/inventory/delete', [
            'handler' => [InventoryController::class, 'destroy'],
            'middleware' => ['auth']
        ]);
    });
});
