<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\QaReportController; 
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware('auth')->group(function () {
    
    Route::get('/ping', function () {
        return 'Halo, server ini merespons dengan baik!';
    });

    // Halaman Form Utama
    Route::get('/', function () {
        $projects = \App\Models\Project::all();
        return view('qa-form', compact('projects'));
    });

    // Pindahkan rute pemrosesan laporan ke sini (tanpa prefix /api)
    Route::post('/qa-reports', [QaReportController::class, 'store']);
    Route::get('/qa-reports/{id}/export', [QaReportController::class, 'export']);
    Route::delete('/qa-reports/{id}', [QaReportController::class, 'destroy']);

    // Rute Update Status Lifecycle
    Route::patch('/findings/{id}/status', [QaReportController::class, 'updateFindingStatus']);

    // Rute profil bawaan Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// FASE 10: DASHBOARD DENGAN METRIK & PAGINATION
Route::get('/dashboard', function (Request $request) {
    $search = $request->input('search');

    // 1. Ambil data untuk Tabel Laporan (dengan Pagination 5 data per halaman)
    $query = \App\Models\Report::with(['project', 'findings'])->latest();

    if ($search) {
        $query->whereHas('project', function($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%');
        })->orWhereHas('findings', function($q) use ($search) {
            $q->where('description', 'like', '%' . $search . '%');
        });
    }
    
    $reports = $query->paginate(5);

    // 2. Agregasi Data Metrik Global untuk Grafik
    $allFindings = \App\Models\Finding::all();
    
    $metrics = [
        'total_bugs' => $allFindings->count(),
        'status_lulus' => $allFindings->where('status', 'Lulus')->count(),
        'status_gagal' => $allFindings->where('status', 'Gagal')->count(),
        'severity_tinggi' => $allFindings->where('severity', 'Tinggi')->count(),
        'severity_sedang' => $allFindings->where('severity', 'Sedang')->count(),
        'severity_rendah' => $allFindings->where('severity', 'Rendah')->count(),
    ];
    
    return view('dashboard', compact('reports', 'search', 'metrics'));
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';