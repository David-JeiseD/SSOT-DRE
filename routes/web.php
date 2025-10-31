<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Importar todos los controladores que usaremos
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IngestaController;
use App\Http\Controllers\GeneradorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TipoDocumentoController;
use App\Http\Controllers\Admin\MetaController;
use App\Http\Controllers\ReporteMetasController;
use App\Http\Controllers\DniController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
use Illuminate\Support\Facades\DB; // <-- ¡Añade esto al principio!

Route::get('/debug-db', function () {
    try {
        DB::connection()->getPdo();
        dd("Conectado exitosamente a la base de datos: " . DB::connection()->getDatabaseName());
    } catch (\Exception $e) {
        dd("No se pudo conectar a la base de datos. Error: " . $e->getMessage());
    }
});

Route::get('/', function () {
    return view('welcome');
});

// Rutas de Autenticación generadas por laravel/ui
Auth::routes();

// Ruta de Home (a menudo se coloca fuera del grupo de middleware 'auth' 
// para que el middleware 'guest' pueda redirigir aquí si ya estás logueado)
Route::get('/home', [HomeController::class, 'index'])->name('home');


// --- Rutas que requieren que el usuario esté autenticado ---
Route::middleware(['auth'])->group(function () {

    // --- API Interna para consulta de DNI ---
    Route::post('/consultar-dni', [DniController::class, 'obtenerDatos'])->name('dni.obtener');

    // --- Perfil de Usuario ---
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // --- Ingesta y Generación (Roles: Admin o Encargado) ---
    Route::middleware(['role:admin|encargado'])->group(function () {
        // Ingesta
        Route::get('/ingesta/subir', [IngestaController::class, 'create'])->name('ingesta.create');
        Route::post('/ingesta/subir', [IngestaController::class, 'store'])->name('ingesta.store');
        
        // Generador de Expedientes
        Route::get('/generador', [GeneradorController::class, 'index'])->name('generador.index');
        Route::post('/generador', [GeneradorController::class, 'generate'])->name('generador.generate');
    });

    // --- Panel de Administración (Rol: Admin) ---
    Route::middleware(['role:admin'])->name('admin.')->prefix('admin')->group(function () {
        
        Route::resource('users', UserController::class);
        Route::resource('tipos-documento', TipoDocumentoController::class);

        Route::get('/metas', [MetaController::class, 'index'])->name('metas.index');
        Route::post('/metas', [MetaController::class, 'store'])->name('metas.store');
        
        Route::get('/reportes/metas-usuario', [ReporteMetasController::class, 'index'])->name('reportes.metas.index');
    });
});