<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarListingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\CareerController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\EnquiryController;



// Public
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact');
Route::post('/enquiries', [EnquiryController::class, 'store'])->middleware('throttle:enquiries');
Route::get('/settings', [SettingsController::class, 'index']);
Route::get('/careers', [CareerController::class, 'index']);
Route::get('/faqs', [FaqController::class, 'index']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{slug}', [ArticleController::class, 'show']);
Route::get('/listings', [CarListingController::class, 'index']);
Route::get('/listings/{key}', [CarListingController::class, 'show']);

// Protected (requires "Authorization: Bearer <token>")
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/listings', [CarListingController::class, 'store']);
    Route::put('/listings/{carListing}', [CarListingController::class, 'update']);
    Route::patch('/listings/{carListing}/status', [CarListingController::class, 'updateStatus']);
    Route::patch('/listings/{carListing}/discount', [CarListingController::class, 'updateDiscount']);
    Route::delete('/listings/{carListing}', [CarListingController::class, 'destroy']);

    Route::get('/admin/careers', [CareerController::class, 'adminIndex']);
    Route::get('/admin/careers/{careerOpening}', [CareerController::class, 'show']);
    Route::post('/careers', [CareerController::class, 'store']);
    Route::match(['put', 'patch'], '/careers/{careerOpening}', [CareerController::class, 'update']);
    Route::delete('/careers/{careerOpening}', [CareerController::class, 'destroy']);

    Route::get('/admin/faqs', [FaqController::class, 'adminIndex']);
    Route::post('/faqs/reorder', [FaqController::class, 'reorder']);
    Route::post('/faqs', [FaqController::class, 'store']);
    Route::match(['put', 'patch'], '/faqs/{faq}', [FaqController::class, 'update']);
    Route::delete('/faqs/{faq}', [FaqController::class, 'destroy']);

    Route::get('/admin/articles', [ArticleController::class, 'adminIndex']);
    Route::get('/admin/articles/{article}', [ArticleController::class, 'adminShow']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::match(['put', 'patch'], '/articles/{article}', [ArticleController::class, 'update']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);

    Route::get('/admin/enquiries', [EnquiryController::class, 'index']);
    Route::get('/admin/enquiries/summary', [EnquiryController::class, 'summary']);
    Route::patch('/enquiries/{enquiry}', [EnquiryController::class, 'update']);
    Route::delete('/enquiries/{enquiry}', [EnquiryController::class, 'destroy']);

    Route::put('/settings/{group}', [SettingsController::class, 'update']);

    Route::post('/uploads', [UploadController::class, 'store']);
    Route::delete('/uploads', [UploadController::class, 'destroy']);
});
