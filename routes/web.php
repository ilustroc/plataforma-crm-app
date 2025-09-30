<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientsControllers;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ReporteGestionesController;
use App\Http\Controllers\ReportePagosController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\PlaceholdersPagosController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientesCargaController;
use App\Http\Controllers\AutorizacionController;
use App\Http\Controllers\PromesaPdfController;
use App\Http\Controllers\CnaController;

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
| Utilidades (solo auth + role)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','role:administrador,sistemas'])->get('/__last-error', function () {
    $files = glob(storage_path('logs/laravel-*.log'));
    rsort($files);
    $file = $files[0] ?? storage_path('logs/laravel.log');

    if (!is_file($file)) {
        return response('<pre>Sin logs en storage/logs.</pre>', 200)
            ->header('Content-Type','text/html');
    }

    $n = (int) request('n', 300);
    $n = max(50, min(2000, $n));
    $lines = @file($file, FILE_IGNORE_NEW_LINES) ?: [];
    $tail  = implode("\n", array_slice($lines, -$n));

    return response('<pre style="white-space:pre-wrap">'.e($tail).'</pre>', 200)
        ->header('Content-Type','text/html');
})->name('__last_error');

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
    | Clientes (búsqueda, ficha y acciones relacionadas)
    |--------------------------------------------------------------------------
    */
    Route::prefix('clientes')->group(function () {
        Route::get('/',      [ClientsControllers::class,'index'])->name('clientes.index');
        Route::get('/{dni}', [ClientsControllers::class,'show'])->name('clientes.show');

        // Promesas de pago (crear desde la ficha del cliente)
        Route::post('/{dni}/promesas', [ClientsControllers::class,'storePromesa'])
            ->name('clientes.promesas.store');

        // CNA (crear solicitud desde la ficha del cliente)
        Route::post('/{dni}/cnas', [CnaController::class, 'store'])
            ->name('clientes.cna.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Reportes
    |--------------------------------------------------------------------------
    */
    Route::prefix('reportes')->group(function () {
        // Gestiones
        Route::get('/gestiones',        [ReporteGestionesController::class, 'index'])->name('reportes.gestiones');
        Route::get('/gestiones/export', [ReporteGestionesController::class, 'export'])->name('reportes.gestiones.export');

        // Pagos
        Route::get('/pagos',        [ReportePagosController::class, 'index'])->name('reportes.pagos');
        Route::get('/pagos/export', [ReportePagosController::class, 'export'])->name('reportes.pagos.export');

        // Otros
        Route::view('/pdp', 'reportes.pdp')->name('reportes.pdp');
    });

    /*
    |--------------------------------------------------------------------------
    | Autorización (Promesas + CNA)
    |--------------------------------------------------------------------------
    */
    // Bandeja (muestra lo que corresponde según rol)
    Route::get('/autorizacion', [AutorizacionController::class,'index'])->name('autorizacion');

    // --- Promesas: supervisor ---
    Route::middleware('role:supervisor')->group(function () {
        Route::post('/autorizacion/{promesa}/preaprobar',   [AutorizacionController::class,'preaprobar'])->name('autorizacion.preaprobar');
        Route::post('/autorizacion/{promesa}/rechazar-sup', [AutorizacionController::class,'rechazarSup'])->name('autorizacion.rechazar.sup');
    });

    // --- Promesas: administrador ---
    Route::middleware('role:administrador')->group(function () {
        Route::post('/autorizacion/{promesa}/aprobar',        [AutorizacionController::class,'aprobar'])->name('autorizacion.aprobar');
        Route::post('/autorizacion/{promesa}/rechazar-admin', [AutorizacionController::class,'rechazarAdmin'])->name('autorizacion.rechazar.admin');
    });

    // PDF de la propuesta (aprobadas)
    Route::middleware('role:administrador,supervisor')->get(
        '/promesas/{promesa}/acuerdo',
        [PromesaPdfController::class, 'acuerdo']
    )->name('promesas.acuerdo');

    /*
    |--------------------------------------------------------------------------
    | CNA – Flujo de aprobación
    |--------------------------------------------------------------------------
    */
    // Supervisor
    Route::middleware('role:supervisor')->group(function () {
        Route::post('/cna/{id}/preaprobar',   [CnaController::class,'preaprobar'])->name('cna.preaprobar')->whereNumber('id');
        Route::post('/cna/{id}/rechazar-sup', [CnaController::class,'rechazarSup'])->name('cna.rechazar.sup')->whereNumber('id');
    });

    // Administrador
    Route::middleware('role:administrador')->group(function () {
        Route::post('/cna/{id}/aprobar',        [CnaController::class,'aprobar'])->name('cna.aprobar')->whereNumber('id');
        Route::post('/cna/{id}/rechazar-admin', [CnaController::class,'rechazarAdmin'])->name('cna.rechazar.admin')->whereNumber('id');
    });

    // Descargas (PDF / DOCX)
    Route::get('/cna/{id}/pdf',  [CnaController::class, 'pdf'])->name('cna.pdf')->whereNumber('id');
    Route::get('/cna/{id}/docx', [CnaController::class, 'docx'])->name('cna.docx')->whereNumber('id');

    /*
    |--------------------------------------------------------------------------
    | Admin / Soporte
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:administrador,sistemas')->group(function () {

        // Integración ▸ Data
        Route::view('/integracion/data', 'placeholders.integracion-data')->name('integracion.data');

        // Clientes (master)
        Route::get('/integracion/data/clientes/template', [ClientesCargaController::class, 'templateClientesMaster'])
            ->name('integracion.data.clientes.template');
        Route::post('/integracion/data/clientes/import',  [ClientesCargaController::class, 'importClientesMaster'])
            ->name('integracion.data.clientes.import');

        // Integración ▸ Pagos
        Route::get('/integracion/pagos',          [PlaceholdersPagosController::class, 'index'])->name('integracion.pagos');
        Route::post('/integracion/pagos/import',  [PlaceholdersPagosController::class, 'import'])->name('integracion.pagos.import');
        Route::get('/integracion/pagos/template', [PlaceholdersPagosController::class, 'template'])->name('integracion.pagos.template');

        // Caja Cusco ▸ Castigada
        Route::post('/integracion/pagos/import/cusco-castigada',  [PlaceholdersPagosController::class, 'importCajaCuscoCastigada'])->name('integracion.pagos.import.cusco');
        Route::get('/integracion/pagos/template/cusco-castigada', [PlaceholdersPagosController::class, 'templateCajaCuscoCastigada'])->name('integracion.pagos.template.cusco');

        // Caja Cusco ▸ Extrajudicial
        Route::get('/integracion/pagos/template/cusco-extrajudicial', [PlaceholdersPagosController::class, 'templateCajaCuscoExtrajudicial'])->name('integracion.pagos.template.cusco_extrajudicial');
        Route::post('/integracion/pagos/import/cusco-extrajudicial',  [PlaceholdersPagosController::class, 'importCajaCuscoExtrajudicial'])->name('integracion.pagos.import.cusco_extrajudicial');

        // Administración de usuarios
        Route::get('/administracion',                           [AdminUsersController::class, 'index'])->name('administracion');
        Route::post('/administracion/supervisores',             [AdminUsersController::class, 'storeSupervisor'])->name('administracion.supervisores.store');
        Route::post('/administracion/asesores',                 [AdminUsersController::class, 'storeAsesor'])->name('administracion.asesores.store');
        Route::patch('/administracion/asesores/{id}/reasignar', [AdminUsersController::class, 'reassignAsesor'])->name('administracion.asesores.reassign');
    });

    // Zonas por rol (opcional)
    Route::middleware('role:administrador')->get('/admin', fn () => 'Zona Admin');
    Route::middleware('role:supervisor')->get('/supervisor', fn () => 'Zona Supervisor');
    Route::middleware('role:sistemas')->get('/sistemas', fn () => 'Zona Sistemas');
});

/*
|--------------------------------------------------------------------------
| Compatibilidad
|--------------------------------------------------------------------------
*/
Route::any('/index.php', fn () => redirect('/'));
Route::get('/home', fn () => redirect('/'));
