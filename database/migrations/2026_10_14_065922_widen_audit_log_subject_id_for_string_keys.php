<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('subject_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::getConnection();
        $subjectIds = $connection->table('audit_logs')->whereNotNull('subject_id');

        $hasNonNumericSubjectIds = match ($connection->getDriverName()) {
            'mysql', 'mariadb' => $subjectIds->whereRaw("subject_id NOT REGEXP '^[0-9]+$'")->exists(),
            'sqlite' => $subjectIds->whereRaw("subject_id = '' OR subject_id GLOB '*[^0-9]*'")->exists(),
            'pgsql' => $subjectIds->whereRaw("subject_id !~ '^[0-9]+$'")->exists(),
            default => throw new RuntimeException('Cannot safely check audit subject IDs for this database driver.'),
        };

        if ($hasNonNumericSubjectIds) {
            throw new RuntimeException('Cannot restore numeric audit subject IDs while string subject IDs exist.');
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('subject_id')->nullable()->change();
        });
    }
};
