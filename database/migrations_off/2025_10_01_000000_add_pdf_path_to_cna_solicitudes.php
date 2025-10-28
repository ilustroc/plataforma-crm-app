<?php
// database/migrations/2025_10_01_000000_add_pdf_path_to_cna_solicitudes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('cna_solicitudes', function (Blueprint $t) {
            if (!Schema::hasColumn('cna_solicitudes', 'pdf_path')) {
                $t->string('pdf_path')->nullable()->after('docx_path');
            }
        });
    }
    public function down(): void {
        Schema::table('cna_solicitudes', function (Blueprint $t) {
            if (Schema::hasColumn('cna_solicitudes', 'pdf_path')) {
                $t->dropColumn('pdf_path');
            }
        });
    }
};
