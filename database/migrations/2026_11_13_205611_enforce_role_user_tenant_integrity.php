
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Required by PostgreSQL for the composite foreign key.
        Schema::table('roles', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'id'],
                'roles_company_id_id_unique'
            );
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->after('id')
                ->constrained()
                ->restrictOnDelete();

            $table->dropForeign(['user_id']);
            $table->dropForeign(['role_id']);

            $table->unique(
                ['company_id', 'user_id', 'role_id'],
                'role_user_company_user_role_unique'
            );

            $table->foreign(
                ['company_id', 'user_id'],
                'role_user_company_user_foreign'
            )
                ->references(['company_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign(
                ['company_id', 'role_id'],
                'role_user_company_role_foreign'
            )
                ->references(['company_id', 'id'])
                ->on('roles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign('role_user_company_user_foreign');
            $table->dropForeign('role_user_company_role_foreign');

            $table->dropUnique('role_user_company_user_role_unique');

            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_company_id_id_unique');
        });
    }
};
