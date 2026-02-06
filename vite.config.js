import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    tailwindcss(),
    laravel({
      input: [
        // Archivos del Dashboard
        'resources/css/app.css',
        'resources/js/app.js',
                
        // Archivos del Login
        'resources/css/login.css',
        'resources/js/login.js',

        // Archivos del Resumen
        'resources/css/resumen.css',
        'resources/js/resumen.js',

        // Archivos del Cliente
        'resources/css/cliente.css',
        'resources/js/cliente.js',

        // Archivos del Dashboard
        'resources/css/dashboard.css',
        'resources/js/dashboard.js',
        
        // Archivos de Autorizacion
        'resources/css/autorizacion.css',
        'resources/js/autorizacion.js',

        // Archivos de Reporte de pagos
        'resources/css/reporte_pagos.css',
        'resources/js/reporte_pagos.js',

        // Archivos de Reporte de Promesas
        'resources/css/reporte_promesas.css',
        'resources/js/reporte_promesas.js',

        // Archivos de Importar Pagos
        'resources/css/importar_pagos.css',
        'resources/js/importar_pagos.js',

        // Archivos de Importar Carteras
        'resources/css/importar_carteras.css',
        'resources/js/importar_carteras.js',

        // Archivos de Admin
        'resources/css/admin.css',
        'resources/js/admin.js',
      ],
      refresh: true,
    }),
  ],
});
