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
        Schema::table('table_relation', function (Blueprint $table) {
                    DB::statement("

                        CREATE TABLE `table_relation` (
	`table_relation_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`table_relation_table_name` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_general_ci',
	`table_relation_table_id` BIGINT(20) NOT NULL,
	`table_relation_unique_id` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_general_ci',
	`table_relation_created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
	PRIMARY KEY (`table_relation_id`) USING BTREE,
	INDEX `table_relation_unique_id` (`table_relation_unique_id`) USING BTREE
)
COLLATE='utf8mb3_general_ci'
ENGINE=InnoDB

                    ");
                });


        Schema::table('table_changes', function (Blueprint $table) {
                    DB::statement("

                        CREATE TABLE `table_changes` (
    `table_changes_id` INT(11) NOT NULL AUTO_INCREMENT,
    `table_changes_table_name` VARCHAR(150) NOT NULL COLLATE 'utf8mb3_general_ci',
    `table_changes_table_id` BIGINT(20) NOT NULL DEFAULT '0',
    `table_changes_updated_at` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`table_changes_id`) USING BTREE,
    UNIQUE INDEX `table_changes_table_name_table_changes_table_id` (`table_changes_table_name`, `table_changes_table_id`) USING BTREE,
    INDEX `table_changes_updated_at` (`table_changes_updated_at`) USING BTREE
)
COLLATE='utf8mb3_general_ci'
ENGINE=InnoDB

                    ");
                });


        Schema::table('project_rest_event', function (Blueprint $table) {
                    DB::statement("



CREATE PROCEDURE `project_rest_event`(IN `table_name` VARCHAR(150),IN `table_id` INT)
    LANGUAGE SQL
    DETERMINISTIC
    CONTAINS SQL
    SQL SECURITY DEFINER
    COMMENT ''
    BEGIN

        INSERT INTO table_changes (table_changes_table_name, table_changes_table_id, table_changes_updated_at)

			SELECT table_name, table_id, NOW()

			ON DUPLICATE KEY UPDATE
			table_changes_updated_at = NOW();

    END

                    ");
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_relation');
        Schema::dropIfExists('table_changes');

        Schema::table('project_rest_event', function (Blueprint $table) {
                    DB::statement("

                        DROP PROCEDURE IF EXISTS project_rest_event

                    ");
                });
    }
};
