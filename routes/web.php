<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesControllers;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ReporteGestionesController;
use App\Http\Controllers\ReportePagosController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Integracion\PagosImportController;
use App\Http\Controllers\Integracion\CarterasImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AutorizacionController;
use App\Http\Controllers\PromesaPdfController;
use App\Http\Controllers\CnaController;
use App\Http\Controllers\ReportePromesasController;

/**
 * --------------------------------------------------------------------------
 * Rutas Web (web.php)
 * --------------------------------------------------------------------------
 * Convenciones:
 * - Rutas públicas: solo login (guest).
 * - Rutas protegidas: requieren autenticación (auth).
 * - Acceso por rol: se controla con middleware role:{rol}.
 * - Nombres de ruta: se usan prefijos por módulo para mantener consistencia.
 * --------------------------------------------------------------------------
 */

/*
|--------------------------------------------------------------------------
| Acceso público (Invitados)
|--------------------------------------------------------------------------
| Pantallas disponibles sin autenticación.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Cierre de sesión
|--------------------------------------------------------------------------
| Disponible solo para usuarios autenticados.
*/
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Área protegida (Usuarios autenticados)
|--------------------------------------------------------------------------
| Todo lo que esté dentro de este grupo requiere sesión iniciada.
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Inicio / Panel principal
    |--------------------------------------------------------------------------
    */
    Route::get('/', [PanelController::class, 'index'])->name('panel');
    Route::redirect('/panel', '/');

    /*
    |--------------------------------------------------------------------------
    | Dashboard (KPIs / Resumen)
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    | - Listado y detalle por DNI.
    | - Acciones asociadas al cliente (promesas, CNA, etc.).
    */
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClientesControllers::class, 'index'])->name('clientes.index');
        Route::get('/{dni}', [ClientesControllers::class, 'show'])->name('clientes.show');

        // Registro de promesa desde la vista del cliente
        Route::post('/{dni}/promesas', [ClientesControllers::class, 'storePromesa'])
            ->name('clientes.promesas.store');

        // Registro de solicitud CNA desde la vista del cliente
        Route::post('/{dni}/cnas', [CnaController::class, 'store'])
            ->name('clientes.cna.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Reportes
    |--------------------------------------------------------------------------
    | Endpoints de consulta y exportación para reportes operativos.
    */
    Route::prefix('reportes')->group(function () {
        // Gestiones
        Route::get('/gestiones', [ReporteGestionesController::class, 'index'])->name('reportes.gestiones');
        Route::get('/gestiones/export', [ReporteGestionesController::class, 'export'])->name('reportes.gestiones.export');

        // Pagos
        Route::get('/pagos', [ReportePagosController::class, 'index'])->name('reportes.pagos.index');
        Route::get('/pagos/export', [ReportePagosController::class, 'export'])->name('reportes.pagos.export');

        // Promesas
        Route::get('/promesas', [ReportePromesasController::class, 'index'])
            ->name('reportes.promesas.index');

        Route::get('/promesas/export', [ReportePromesasController::class, 'export'])
            ->name('reportes.promesas.export');
    });

    /*
    |--------------------------------------------------------------------------
    | Autorización (Promesas + CNA)
    |--------------------------------------------------------------------------
    | Flujo de aprobaciones por roles:
    | - Supervisor: preaprueba / rechaza (nivel 1).
    | - Administrador: aprueba / rechaza (nivel final).
    */
    Route::get('/autorizacion', [AutorizacionController::class, 'index'])->name('autorizacion');
    Route::get('/autorizacion/pagos/{dni}', [AutorizacionController::class, 'pagosDni'])->name('autorizacion.pagos');

    // Acciones del supervisor (nivel 1)
    Route::middleware('role:supervisor')->group(function () {
        Route::post('/autorizacion/{promesa}/preaprobar', [AutorizacionController::class, 'preaprobar'])
            ->name('autorizacion.preaprobar');

        Route::post('/autorizacion/{promesa}/rechazar-sup', [AutorizacionController::class, 'rechazarSup'])
            ->name('autorizacion.rechazar.sup');
    });

    // Acciones del administrador (nivel final)
    Route::middleware('role:administrador')->group(function () {
        Route::post('/autorizacion/{promesa}/aprobar', [AutorizacionController::class, 'aprobar'])
            ->name('autorizacion.aprobar');

        Route::post('/autorizacion/{promesa}/rechazar-admin', [AutorizacionController::class, 'rechazarAdmin'])
            ->name('autorizacion.rechazar.admin');
    });

    /*
    |--------------------------------------------------------------------------
    | Promesas - Documentos (PDF)
    |--------------------------------------------------------------------------
    | Generación de acuerdo/constancia en PDF para una promesa específica.
    */
    Route::middleware('auth')->get(
        '/promesas/{promesa}/acuerdo',
        [PromesaPdfController::class, 'acuerdo']
    )->name('promesas.acuerdo');

    /*
    |--------------------------------------------------------------------------
    | CNA - Flujo de aprobación
    |--------------------------------------------------------------------------
    | Acciones y generación de documentos asociados a CNA.
    */
    Route::middleware('role:supervisor')->group(function () {
        Route::post('/cna/{cna}/preaprobar', [CnaController::class, 'preaprobar'])->name('cna.preaprobar');
        Route::post('/cna/{cna}/rechazar-sup', [CnaController::class, 'rechazarSup'])->name('cna.rechazar.sup');
    });

    Route::middleware('role:administrador')->group(function () {
        Route::post('/cna/{cna}/aprobar', [CnaController::class, 'aprobar'])->name('cna.aprobar');
        Route::post('/cna/{cna}/rechazar-admin', [CnaController::class, 'rechazarAdmin'])->name('cna.rechazar.admin');
    });

    // Descarga de documentos (solo Supervisor / Administrador)
    Route::middleware('role:administrador,supervisor')->group(function () {
        Route::get('/cna/{id}/pdf', [CnaController::class, 'pdf'])->name('cna.pdf');
        Route::get('/cna/{id}/docx', [CnaController::class, 'docx'])->name('cna.docx');
    });

    /*
    |--------------------------------------------------------------------------
    | Administración (solo rol: administrador)
    |--------------------------------------------------------------------------
    | Incluye integración de datos y gestión de usuarios.
    */
    Route::middleware('role:administrador')->group(function () {

        /*
        |----------------------------------------------------------------------
        | Integración de datos
        |----------------------------------------------------------------------
        | - Carteras: subida de data y descarga de plantilla.
        | - Pagos: carga y descarga de plantilla.
        */
        Route::prefix('integracion')->group(function () {

            // Vista principal de integración de carteras
            Route::view('/carteras', 'integracion.carteras')->name('carteras');

            // Carteras (Cartera Master)
            Route::prefix('data/carteras')->name('integracion.carteras.')->group(function () {

                // Plantilla CSV
                Route::get('/template', [CarterasImportController::class, 'templateCarterasMaster'])
                    ->name('template');

                // Importación CSV
                Route::post('/import', [CarterasImportController::class, 'importCarterasMaster'])
                    ->name('import');
            });

            // Pagos (vista + carga + plantilla)
            Route::get('pagos', [PagosImportController::class, 'create'])
                ->name('integracion.pagos');

            Route::post('pagos/store', [PagosImportController::class, 'store'])
                ->name('integracion.pagos.store');

            Route::get('pagos/template', [PagosImportController::class, 'template'])
                ->name('integracion.pagos.template');
        });

        /*
        |----------------------------------------------------------------------
        | Gestión de usuarios (CRUD + acciones)
        |----------------------------------------------------------------------
        | Prefijo URL:    /admin/users
        | Prefijo nombre: admin.users.*
        */
        Route::prefix('admin/users')->name('admin.users.')->group(function () {

            // Listado y creación
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');

            // Operaciones sobre un usuario específico
            Route::prefix('{user}')->group(function () {

                // CRUD básico
                Route::patch('/', [UserController::class, 'update'])->name('update');
                Route::delete('/', [UserController::class, 'destroy'])->name('destroy');

                // Acciones adicionales (UI / modales)
                Route::patch('/toggle', [UserController::class, 'toggle'])->name('toggle');
                Route::patch('/password', [UserController::class, 'password'])->name('password');
                Route::patch('/reassign', [UserController::class, 'reassign'])->name('reassign');
                Route::patch('/role', [UserController::class, 'setRole'])->name('role');
            });
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Rutas de prueba por rol (opcional)
    |--------------------------------------------------------------------------
    | Útiles para validar rápidamente permisos y middleware.
    */
    Route::middleware('role:administrador')->get('/admin', fn () => 'Zona Admin');
    Route::middleware('role:supervisor')->get('/supervisor', fn () => 'Zona Supervisor');
});

/*
|--------------------------------------------------------------------------
| Compatibilidad / Redirecciones
|--------------------------------------------------------------------------
| Evita accesos a endpoints antiguos y normaliza rutas.
*/
Route::any('/index.php', fn () => redirect('/'));
Route::get('/home', fn () => redirect('/'));
