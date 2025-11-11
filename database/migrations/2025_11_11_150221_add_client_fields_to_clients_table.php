<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (! Schema::hasColumn('clients', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('clients', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('clients', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('clients', 'currency_rate')) {
                $table->decimal('currency_rate', 10, 4)->nullable();
            }
        });

        // Добавляем индексы только если колонки существуют и индексы еще не созданы
        if (Schema::hasColumn('clients', 'name') && ! $this->indexExists('clients', 'clients_name_index')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->index('name');
            });
        }
        if (Schema::hasColumn('clients', 'phone') && ! $this->indexExists('clients', 'clients_phone_index')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->index('phone');
            });
        }
        if (Schema::hasColumn('clients', 'email') && ! $this->indexExists('clients', 'clients_email_index')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->index('email');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['name']);
            $table->dropColumn(['name', 'phone', 'email', 'address', 'currency_rate']);
        });
    }
};
