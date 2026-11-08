<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeThreeController;
use App\Http\Controllers\HomeTwoController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/home-2', HomeTwoController::class)->name('home-2');
Route::get('/home-3', HomeThreeController::class)->name('home-3');
Route::get('/destinations', [SiteController::class, 'destinations'])->name('destinations');
Route::get('/information', [SiteController::class, 'information'])->name('information');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/contacts', [SiteController::class, 'contacts'])->name('contacts');
Route::post('/contacts/request', [SiteController::class, 'contactRequest'])->middleware('throttle:60,1')->name('contacts.request');
Route::post('/transfer-enquiry', [SiteController::class, 'transferEnquiry'])->middleware('throttle:60,1')->name('transfer.enquiry');
Route::post('/transfer/continue', [SiteController::class, 'transferContinue'])->middleware('throttle:60,1')->name('transfer.continue');
Route::get('/transfer/request', [SiteController::class, 'transferRequest'])->name('transfer.request');
Route::post('/transfer/request', [SiteController::class, 'transferRequestStore'])->middleware('throttle:60,1')->name('transfer.request.store');
Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/personal-data-consent', [LegalController::class, 'consent'])->name('legal.consent');

Route::get('/demo/v-class-scene', fn () => response()
    ->view('demo.v-class-scene')
    ->header('X-Robots-Tag', 'noindex, nofollow'))
    ->name('demo.v-class-scene');

Route::get('/demo/v-class-scene-v2', fn () => response()
    ->view('demo.v-class-scene-v2')
    ->header('X-Robots-Tag', 'noindex, nofollow'))
    ->name('demo.v-class-scene-v2');

Route::get('/demo/v-class-scene-v3', fn () => response()
    ->view('demo.v-class-scene-v3')
    ->header('X-Robots-Tag', 'noindex, nofollow'))
    ->name('demo.v-class-scene-v3');



Route::get('/{destination:slug}', [SiteController::class, 'destination'])
    ->where('destination', '^(?!admin$|blog$|contacts$)[A-Za-z0-9-]+')
    ->name('destination');


Route::post('/{destination:slug}/request', [SiteController::class, 'request'])
    ->middleware('throttle:60,1')
    ->where('destination', '^(?!admin$|blog$|contacts$)[A-Za-z0-9-]+')
    ->name('destination.request');
