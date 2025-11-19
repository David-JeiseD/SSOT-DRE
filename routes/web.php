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
use App\Http\Controllers\Api\ConstanciaController;
use App\Http\Controllers\Api\DatoCrudoController;
use App\Http\Controllers\Admin\PlantillaController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\Admin\ColumnaMaestraController;
use App\Http\Controllers\Admin\DatoGestionController;

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

Route::get('/home', [HomeController::class, 'index'])->name('home');

// --- Rutas que requieren que el usuario esté autenticado ---
Route::middleware(['auth'])->group(function () {


    Route::get('/usuarios/buscar', [UserController::class, 'buscar'])->name('api.usuarios.buscar');
    Route::get('/constancias/buscar', [ConstanciaController::class, 'buscar'])->name('api.constancias.buscar');
    // --- API Interna para consulta de DNI ---
    Route::post('/consultar-dni', [DniController::class, 'obtenerDatos'])->name('dni.obtener');
    Route::post('/expedientes/verificar-existencia', [GeneradorController::class, 'verificarExistenciaExpediente'])->name('api.expedientes.verificar');
    // --- Perfil de Usuario ---
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // --- Ingesta y Generación (Roles: Admin o Encargado) ---
    Route::middleware(['role:admin|encargado'])->group(function () {
        // Ingesta
        Route::get('/ingesta/subir', [IngestaController::class, 'create'])->name('ingesta.create');
        Route::post('/ingesta/subir', [IngestaController::class, 'store'])->name('ingesta.store');
        
        // Grupo de rutas para el Generador de Documentos
        Route::prefix('generador')->name('generador.')->group(function () {
            Route::get('/', [GeneradorController::class, 'index'])->name('index'); 
            Route::post('/buscar', [GeneradorController::class, 'buscarDatos'])->name('buscar');
            Route::post('/previsualizar', [GeneradorController::class, 'previsualizar'])->name('previsualizar');
            
            Route::post('/generar-final', [GeneradorController::class, 'generarFinal'])->name('generarFinal');
        });
    });

    // --- Panel de Administración (Rol: Admin) ---
    Route::middleware(['role:admin'])->name('admin.')->prefix('admin')->group(function () {
        
        Route::resource('users', UserController::class);
        Route::get('users/{user}/detalle', [UserController::class, 'detalle'])->name('users.detalle');
        Route::resource('tipos-documento', TipoDocumentoController::class)->except(['index']);

        Route::get('/metas', [MetaController::class, 'index'])->name('metas.index');
        Route::post('/metas', [MetaController::class, 'store'])->name('metas.store');
        Route::get('/reportes/metas-usuario', [ReporteMetasController::class, 'index'])->name('reportes.metas.index');
        Route::put('/datos-crudos/{idFilaOrigen}', [DatoCrudoController::class, 'update']);
        Route::delete('/datos-crudos/{idFilaOrigen}', [DatoCrudoController::class, 'destroy']);
        //Route::post('/plantillas/generar-personalizada', [PlantillaController::class, 'generarPersonalizada'])->name('plantillas.generarPersonalizada');
        // --- NUEVA RUTA PARA GESTIÓN DE COLUMNAS MAESTRAS ---
        Route::resource('columnas-maestras', ColumnaMaestraController::class)->except(['show']);
        
        Route::prefix('gestion-datos')->name('gestion-datos.')->group(function () {
        
            // Muestra la página de búsqueda de usuario
            Route::get('/', [DatoGestionController::class, 'index'])->name('index');
            
            // Muestra la tabla de datos para un usuario específico
            Route::get('/{user}', [DatoGestionController::class, 'show'])->name('show');
    
            // (Las rutas para store, update, destroy ya las tenemos en DatoCrudoController, las reutilizaremos)
            // (Podríamos añadir una ruta POST para crear un nuevo registro completo si es necesario)
            Route::post('/{user}', [DatoGestionController::class, 'store'])->name('store'); // Para crear un nuevo registro de pago
    
        });

    });

    Route::middleware(['role:admin|encargado|consultor'])->group(function () {
        // Esta ruta mostrará el formulario de búsqueda y los resultados
        Route::get('/expedientes', [ExpedienteController::class, 'index'])->name('expedientes.index');
        //ruta para VER la lista de tipos de documento
        Route::get('/admin/tipos-documento', [TipoDocumentoController::class, 'index'])->name('admin.tipos-documento.index');
        // Esta ruta se encargará de la descarga del archivo Excel
        Route::get('/expedientes/{expediente}/descargar', [ExpedienteController::class, 'descargar'])->name('expedientes.descargar');
        Route::post('/admin/plantillas/generar-personalizada', [PlantillaController::class, 'generarPersonalizada'])->name('admin.plantillas.generarPersonalizada');
        Route::get('/expedientes/{expediente}', [ExpedienteController::class, 'show'])->name('expedientes.show');
        Route::get('/expedientes/{expediente}/pdf', [ExpedienteController::class, 'descargarPdf'])->name('expedientes.pdf');
    });

});