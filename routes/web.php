<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/destinations', [SiteController::class, 'destinations'])->name('destinations');
Route::get('/information', [SiteController::class, 'information'])->name('information');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/contacts', [SiteController::class, 'contacts'])->name('contacts');
Route::post('/contacts/request', [SiteController::class, 'contactRequest'])->name('contacts.request');



Route::get('/{destination:slug}', [SiteController::class, 'destination'])
    ->where('destination', '^(?!admin$|blog$|contacts$)[A-Za-z0-9-]+')
    ->name('destination');


Route::post('/{destination:slug}/request', [SiteController::class, 'request'])
    ->where('destination', '^(?!admin$|blog$|contacts$)[A-Za-z0-9-]+')
    ->name('destination.request');
