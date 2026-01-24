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
      ],
      refresh: true,
    }),
  ],
});
