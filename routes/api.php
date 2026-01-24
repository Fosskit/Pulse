<?php

use App\Actions\Admin\Activity\ListActivityAction;
use App\Actions\Admin\Permissions\DeletePermissionAction;
use App\Actions\Admin\Permissions\ListPermissionsAction;
use App\Actions\Admin\Permissions\ShowPermissionAction;
use App\Actions\Admin\Permissions\StorePermissionAction;
use App\Actions\Admin\Permissions\UpdatePermissionAction;
use App\Actions\Admin\Roles\AssignPermissionsAction as AssignPermissionsToRoleAction;
use App\Actions\Admin\Roles\DeleteRoleAction;
use App\Actions\Admin\Roles\ListRolesAction;
use App\Actions\Admin\Roles\ShowRoleAction;
use App\Actions\Admin\Roles\StoreRoleAction;
use App\Actions\Admin\Roles\UpdateRoleAction;
use App\Actions\Admin\Users\AssignPermissionsAction as AssignPermissionsToUserAction;
use App\Actions\Admin\Users\AssignRolesAction;
use App\Actions\Admin\Users\DeleteUserAction;
use App\Actions\Admin\Users\ListUsersAction;
use App\Actions\Admin\Users\ShowUserAction;
use App\Actions\Admin\Users\StoreUserAction;
use App\Actions\Admin\Users\UpdateUserAction;
use App\Actions\Auth\ForgotPasswordAction;
use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\MeAction;
use App\Actions\Auth\RefreshTokenAction;
use App\Actions\Auth\RegisterAction;
use App\Actions\Auth\ResendVerificationAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\VerifyEmailAction;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/auth/register', RegisterAction::class);
Route::post('/auth/login', LoginAction::class);
Route::post('/auth/logout', LogoutAction::class)->middleware('auth:api');
Route::get('/auth/me', MeAction::class)->middleware('auth:api');
Route::post('/auth/refresh', RefreshTokenAction::class)->middleware('auth:api');
Route::get('/auth/verify-email/{id}/{hash}', VerifyEmailAction::class)->name('verification.verify');
Route::post('/auth/resend-verification', ResendVerificationAction::class);
Route::post('/auth/forgot-password', ForgotPasswordAction::class);
Route::post('/auth/reset-password', ResetPasswordAction::class);

// Admin routes (protected with permissions only - roles are just containers)
Route::middleware(['auth:api', 'permission:users.view'])->group(function () {
    Route::get('/admin/users', ListUsersAction::class);
    Route::get('/admin/users/{user}', ShowUserAction::class);
    Route::post('/admin/users', StoreUserAction::class)->middleware('permission:users.create');
    Route::put('/admin/users/{user}', UpdateUserAction::class)->middleware('permission:users.update');
    Route::delete('/admin/users/{user}', DeleteUserAction::class)->middleware('permission:users.delete');
    Route::post('/admin/users/{user}/permissions', AssignPermissionsToUserAction::class)->middleware('permission:users.assign-permissions');
    Route::post('/admin/users/{user}/roles', AssignRolesAction::class)->middleware('permission:users.assign-roles');
});

Route::middleware(['auth:api', 'permission:roles.view'])->group(function () {
    Route::get('/admin/roles', ListRolesAction::class);
    Route::get('/admin/roles/{role}', ShowRoleAction::class);
    Route::post('/admin/roles', StoreRoleAction::class)->middleware('permission:roles.create');
    Route::put('/admin/roles/{role}', UpdateRoleAction::class)->middleware('permission:roles.update');
    Route::delete('/admin/roles/{role}', DeleteRoleAction::class)->middleware('permission:roles.delete');
    Route::post('/admin/roles/{role}/permissions', AssignPermissionsToRoleAction::class)->middleware('permission:roles.assign-permissions');
});

Route::middleware(['auth:api', 'permission:permissions.view'])->group(function () {
    Route::get('/admin/permissions', ListPermissionsAction::class);
    Route::get('/admin/permissions/{permission}', ShowPermissionAction::class);
    Route::post('/admin/permissions', StorePermissionAction::class)->middleware('permission:permissions.create');
    Route::put('/admin/permissions/{permission}', UpdatePermissionAction::class)->middleware('permission:permissions.update');
    Route::delete('/admin/permissions/{permission}', DeletePermissionAction::class)->middleware('permission:permissions.delete');
    Route::get('/admin/permission-groups', \App\Actions\Admin\Permissions\ListPermissionGroupsAction::class);
});

Route::middleware(['auth:api', 'permission:activity.view'])->group(function () {
    Route::get('/admin/activity', ListActivityAction::class);
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('/admin/dashboard/stats', \App\Actions\Admin\Dashboard\GetDashboardStatsAction::class);
});

// Reference data routes (generic CRUD for all reference tables)
Route::middleware(['auth:api'])->prefix('references')->group(function () {
    Route::get('/{type}', [\App\Http\Controllers\ReferenceController::class, 'index']);
    Route::get('/{type}/{id}', [\App\Http\Controllers\ReferenceController::class, 'show']);
    Route::post('/{type}', [\App\Http\Controllers\ReferenceController::class, 'store']);
    Route::put('/{type}/{id}', [\App\Http\Controllers\ReferenceController::class, 'update']);
    Route::delete('/{type}/{id}', [\App\Http\Controllers\ReferenceController::class, 'destroy']);
});

// Patient routes
Route::middleware(['auth:api'])->prefix('patients')->group(function () {
    Route::get('/', \App\Actions\Patient\ListPatientsAction::class);
    Route::get('/{patient}', \App\Actions\Patient\ShowPatientAction::class);
    Route::post('/', \App\Actions\Patient\StorePatientAction::class);
    Route::put('/{patient}', \App\Actions\Patient\UpdatePatientAction::class);
    Route::delete('/{patient}', \App\Actions\Patient\DeletePatientAction::class);
});
