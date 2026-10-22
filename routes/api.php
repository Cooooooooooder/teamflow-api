<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\Api\NotificationController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    // workspaces
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy']);

    //members of workspaces
    Route::get('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
    Route::post('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store']);
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy']);

    //tasks workspaces
    Route::get('/workspaces/{workspace}/tasks', [TaskController::class, 'index']);
    Route::post('/workspaces/{workspace}/tasks', [TaskController::class, 'store']);
    Route::patch('/workspaces/{workspace}/tasks/{task}',[TaskController::class, 'update']);
    Route::delete('/workspaces/{workspace}/tasks/{task}',[TaskController::class, 'destroy']);

    //tasks
    Route::get('/tasks/{task}/comments',[TaskCommentController::class, 'index']);
    Route::post('/tasks/{task}/comments',[TaskCommentController::class, 'store']);
    Route::delete('/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy']);

    //notifications
    Route::get('/notifications', [NotificationController::class,'index',]);
    Route::patch('/notifications/read-all', [NotificationController::class,'markAllAsRead',]);
    Route::patch('/notifications/{id}/read', [NotificationController::class,'markAsRead',]);


});
