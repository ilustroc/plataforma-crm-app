<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesControllers;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ReporteGestionesController;
use App\Http\Controllers\ReportePagosController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Integracion\PagosImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientesCargaController;
use App\Http\Controllers\AutorizacionController;
use App\Http\Controllers\PromesaPdfController;
use App\Http\Controllers\CnaController;
use App\Http\Controllers\ReportePromesasController;

/*
|--------------------------------------------------------------------------
| Invitados
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Salir (solo auth)
|--------------------------------------------------------------------------
*/
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Autenticados
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Panel
    Route::get('/', [PanelController::class, 'index'])->name('panel');
    Route::redirect('/panel', '/');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class,'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    */
    Route::prefix('clientes')->group(function () {
        Route::get('/',      [ClientesControllers::class,'index'])->name('clientes.index');
        Route::get('/{dni}', [ClientesControllers::class,'show'])->name('clientes.show');

        Route::post('/{dni}/promesas', [ClientesControllers::class,'storePromesa'])
            ->name('clientes.promesas.store');

        Route::post('/{dni}/cnas', [CnaController::class, 'store'])
            ->name('clientes.cna.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Reportes
    |--------------------------------------------------------------------------
    */
    Route::prefix('reportes')->group(function () {
        Route::get('/gestiones',        [ReporteGestionesController::class, 'index'])->name('reportes.gestiones');
        Route::get('/gestiones/export', [ReporteGestionesController::class, 'export'])->name('reportes.gestiones.export');

        Route::get('/pagos', [ReportePagosController::class, 'index'])->name('reportes.pagos.index');
        Route::get('/pagos/export', [ReportePagosController::class, 'export'])->name('reportes.pagos.export');
        
        Route::get('/pdp',        [ReportePromesasController::class, 'index'])->name('reportes.pdp');
        Route::get('/pdp/export', [ReportePromesasController::class, 'export'])->name('reportes.pdp.export');
    });

    /*
    |--------------------------------------------------------------------------
    | Autorización (Promesas + CNA)
    |--------------------------------------------------------------------------
    */
    Route::get('/autorizacion', [AutorizacionController::class,'index'])->name('autorizacion');
    Route::get('/autorizacion/pagos/{dni}', [AutorizacionController::class, 'pagosDni'])->name('autorizacion.pagos');

    Route::middleware('role:supervisor')->group(function () {
        Route::post('/autorizacion/{promesa}/preaprobar',   [AutorizacionController::class,'preaprobar'])->name('autorizacion.preaprobar');
        Route::post('/autorizacion/{promesa}/rechazar-sup', [AutorizacionController::class,'rechazarSup'])->name('autorizacion.rechazar.sup');
    });

    Route::middleware('role:administrador')->group(function () {
        Route::post('/autorizacion/{promesa}/aprobar',        [AutorizacionController::class,'aprobar'])->name('autorizacion.aprobar');
        Route::post('/autorizacion/{promesa}/rechazar-admin', [AutorizacionController::class,'rechazarAdmin'])->name('autorizacion.rechazar.admin');
    });

    Route::middleware('auth')->get(
        '/promesas/{promesa}/acuerdo',
        [PromesaPdfController::class, 'acuerdo']
    )->name('promesas.acuerdo');

    /*
    |--------------------------------------------------------------------------
    | CNA – Flujo de aprobación
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:supervisor')->group(function () {
        Route::post('/cna/{cna}/preaprobar',   [CnaController::class,'preaprobar'])->name('cna.preaprobar');
        Route::post('/cna/{cna}/rechazar-sup', [CnaController::class,'rechazarSup'])->name('cna.rechazar.sup');
    });

    Route::middleware('role:administrador')->group(function () {
        Route::post('/cna/{cna}/aprobar',        [CnaController::class,'aprobar'])->name('cna.aprobar');
        Route::post('/cna/{cna}/rechazar-admin', [CnaController::class,'rechazarAdmin'])->name('cna.rechazar.admin');
    });

    Route::middleware('role:administrador,supervisor')->group(function () {
        Route::get('/cna/{id}/pdf',  [CnaController::class, 'pdf'])->name('cna.pdf');
        Route::get('/cna/{id}/docx', [CnaController::class, 'docx'])->name('cna.docx');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:administrador')->group(function () {

        // =========================
        // INTEGRACIÓN
        // =========================
        Route::prefix('integracion')->group(function () {

            // Vista principal de Data
            Route::view('data', 'imports.integracion-data')->name('integracion.data');

            // DATA / CLIENTES
            Route::get('data/clientes/template', [ClientesCargaController::class, 'templateClientesMaster'])
                ->name('integracion.data.clientes.template');

            Route::post('data/clientes/import', [ClientesCargaController::class, 'importClientesMaster'])
                ->name('integracion.data.clientes.import');

            // PAGOS
            Route::get('pagos', [PagosImportController::class, 'create'])
                ->name('integracion.pagos');

            Route::post('pagos/import', [PagosImportController::class, 'import'])
                ->name('integracion.pagos.import');

            Route::get('pagos/template', [PagosImportController::class, 'template'])
                ->name('integracion.pagos.template');
        });

        // =========================
        // ADMINISTRACIÓN DE USUARIOS (NUEVO)
        // =========================
        
        // Prefijo URL: /admin/users
        // Prefijo Nombre: admin.users. (ej: admin.users.index)
        Route::prefix('admin/users')->name('admin.users.')->group(function () {
            
            // Listado y Creación
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            
            // Acciones sobre un usuario específico
            Route::prefix('{user}')->group(function() {
                // Actualizar / Eliminar (CRUD básico)
                Route::patch('/', [UserController::class, 'update'])->name('update');
                Route::delete('/', [UserController::class, 'destroy'])->name('destroy');

                // Acciones especiales (Modales)
                Route::patch('/toggle',   [UserController::class, 'toggle'])->name('toggle');
                Route::patch('/password', [UserController::class, 'password'])->name('password');
                Route::patch('/reassign', [UserController::class, 'reassign'])->name('reassign');
                Route::patch('/role',     [UserController::class, 'setRole'])->name('role');
            });
        });

    });


    // Zonas por rol (opcional)
    Route::middleware('role:administrador')->get('/admin', fn () => 'Zona Admin');
    Route::middleware('role:supervisor')->get('/supervisor', fn () => 'Zona Supervisor');
});

/*
|--------------------------------------------------------------------------
| Compatibilidad
|--------------------------------------------------------------------------
*/
Route::any('/index.php', fn () => redirect('/'));
Route::get('/home', fn () => redirect('/'));
