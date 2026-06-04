<?php

use App\Models\BookService;
use Illuminate\Support\Facades\Route;

Route::get('/', function(){
    return redirect('/login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('book-service', 'pages::book-service')->name('book-service');
    Route::livewire('book-services', 'pages::book-services-list')->name('book-services');

    Route::livewire('book-services/{bookService}/assessment', 'pages::assessment-form')
        ->name('assessments.show');

    Route::livewire('book-services/{bookService}/assessment/create', 'pages::assessment-form')
        ->name('assessments.create');

    Route::livewire('book-services/{bookService}/quotation', 'pages::quotation-form')
        ->name('quotations.show');

    Route::livewire('book-services/{bookService}/quotation/create', 'pages::quotation-form')
        ->name('quotations.create');

    Route::livewire('book-services/{bookService}/project', 'pages::project-view')
        ->name('projects.show');

    Route::livewire('book-services/{bookService}/report', 'pages::project-report')
        ->name('projects.report');

    Route::livewire('book-services/{bookService}/invoice', 'pages::invoice-form')
        ->name('invoices.show');

    Route::livewire('book-services/{bookService}/invoice/create', 'pages::invoice-form')
        ->name('invoices.create');

    Route::livewire('assessments', 'pages::assessments-index')->name('assessments.index');
    Route::livewire('quotations', 'pages::quotations-index')->name('quotations.index');
    Route::livewire('invoices', 'pages::invoices-index')->name('invoices.index');

    Route::livewire('users', 'pages::users-index')->name('users.index');
    Route::livewire('company/users', 'pages::company-users-index')->name('company.users.index');
    Route::livewire('reports', 'pages::reports')->name('reports');
    Route::livewire('transactions', 'pages::transactions-index')->name('transactions.index');
});

require __DIR__.'/settings.php';
