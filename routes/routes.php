<?php

use Illuminate\Support\Facades\Route;
use OZiTAG\Tager\Backend\Mail\Enums\MailScope;
use OZiTAG\Tager\Backend\Rbac\Facades\AccessControlMiddleware;
use OZiTAG\Tager\Backend\Mail\Controllers\AdminMailLogsController;
use OZiTAG\Tager\Backend\Mail\Controllers\AdminMailTemplatesController;
use OZiTAG\Tager\Backend\Mail\Controllers\AdminMailController;

Route::group(['prefix' => 'admin/mail', 'middleware' => ['passport:administrators', 'auth:api']], function () {
    Route::get('/info', [AdminMailController::class, 'info']);

    Route::get('/service-templates', [AdminMailController::class, 'serviceTemplates'])->middleware([
        AccessControlMiddleware::scopes(MailScope::EditTemplates)
    ]);

    Route::get('/logs', [AdminMailLogsController::class, 'index'])->middleware([
        AccessControlMiddleware::scopes(MailScope::ViewLogs)
    ]);

    Route::get('/templates', [AdminMailTemplatesController::class, 'index'])->middleware([
        AccessControlMiddleware::scopes(MailScope::ViewTemplates)
    ]);

    Route::get('/templates/{id}', [AdminMailTemplatesController::class, 'view'])->middleware([
        AccessControlMiddleware::scopes(MailScope::ViewTemplates)
    ]);

    Route::put('/templates/{id}', [AdminMailTemplatesController::class, 'update'])->middleware([
        AccessControlMiddleware::scopes(MailScope::EditTemplates)
    ]);
});
