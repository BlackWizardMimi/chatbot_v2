<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('chatbox', 'App\Http\Controllers\ViewController@chatbox')->name('chatbox');

Route::get('/chat', function () {
    return view('chat');
})->name('chat');

// API routes for chat functionality
Route::post('/api/chat', [ChatController::class, 'chat'])->name('api.chat');
Route::post('/api/business-chat', [ChatController::class, 'businessChat'])->name('api.business-chat');

// Your existing customer API routes (if you have them)
Route::get('/api/customers', [CustomerController::class, 'list_customer'])->name('api.customers');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
