<?php

use yii\db\Migration;

/**
 * Adds structured staff evaluations, shared presets, and saved-change history.
 * Existing interview_evaluation rows keep their notes and gain only nullable columns.
 */
class m261011_160000_staff_candidate_evaluation_forms extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // interview_evaluation.interview_evaluation_uuid is utf8_unicode_ci in the
            // existing schema. A utf8mb4 child column cannot reference it.
            $parent = $this->db->createCommand(
                'SELECT CHARACTER_SET_NAME AS charset_name, COLLATION_NAME AS collation_name
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table
                   AND COLUMN_NAME = :column'
            )->bindValues([
                ':table' => $this->db->schema->getRawTableName('{{%interview_evaluation}}'),
                ':column' => 'interview_evaluation_uuid',
            ])->queryOne();
            $charset = is_array($parent) ? $parent['charset_name'] : 'utf8';
            $collation = is_array($parent) ? $parent['collation_name'] : 'utf8_unicode_ci';
            if (!preg_match('/\A[A-Za-z0-9_]+\z/', (string) $charset) || !preg_match('/\A[A-Za-z0-9_]+\z/', (string) $collation)) {
                throw new \RuntimeException('Refusing to create evaluation tables with an unexpected collation.');
            }
            $tableOptions = "CHARACTER SET {$charset} COLLATE {$collation} ENGINE=InnoDB";
        }

        $this->addColumn('{{%interview_evaluation}}', 'title', $this->string(200)->null());
        $this->addColumn('{{%interview_evaluation}}', 'evaluation_date', $this->date()->null());
        $this->addColumn('{{%interview_evaluation}}', 'form_status', $this->string(20)->null());
        $this->addColumn('{{%interview_evaluation}}', 'preset_uuid', $this->char(60)->null());
        $this->addColumn('{{%interview_evaluation}}', 'preset_name', $this->string(200)->null());
        $this->addColumn('{{%interview_evaluation}}', 'schema_json', 'MEDIUMTEXT NULL');
        $this->addColumn('{{%interview_evaluation}}', 'answers_json', 'MEDIUMTEXT NULL');
        $this->addColumn('{{%interview_evaluation}}', 'revision', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%interview_evaluation}}', 'created_by_staff_id', $this->integer()->null());
        $this->addColumn('{{%interview_evaluation}}', 'updated_by_staff_id', $this->integer()->null());
        $this->addColumn('{{%interview_evaluation}}', 'completed_at', $this->dateTime()->null());
        $this->addColumn('{{%interview_evaluation}}', 'completed_by_staff_id', $this->integer()->null());

        $this->createIndex('idx-interview_evaluation-created_by_staff_id', '{{%interview_evaluation}}', 'created_by_staff_id');
        $this->addForeignKey(
            'fk-interview_evaluation-created_by_staff_id',
            '{{%interview_evaluation}}',
            'created_by_staff_id',
            '{{%staff}}',
            'staff_id'
        );
        $this->createIndex('idx-interview_evaluation-updated_by_staff_id', '{{%interview_evaluation}}', 'updated_by_staff_id');
        $this->addForeignKey(
            'fk-interview_evaluation-updated_by_staff_id',
            '{{%interview_evaluation}}',
            'updated_by_staff_id',
            '{{%staff}}',
            'staff_id'
        );

        $this->createTable('{{%interview_evaluation_preset}}', [
            'preset_uuid' => $this->char(60),
            'name' => $this->string(200)->notNull(),
            'schema_json' => 'MEDIUMTEXT NOT NULL',
            'created_by_staff_id' => $this->integer()->notNull(),
            'updated_by_staff_id' => $this->integer()->null(),
            'archived_at' => $this->dateTime()->null(),
            'revision' => $this->integer()->notNull()->defaultValue(1),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);
        $this->addPrimaryKey('PK', '{{%interview_evaluation_preset}}', 'preset_uuid');
        $this->createIndex('idx-interview_evaluation_preset-created_by', '{{%interview_evaluation_preset}}', 'created_by_staff_id');
        $this->addForeignKey(
            'fk-interview_evaluation_preset-created_by',
            '{{%interview_evaluation_preset}}',
            'created_by_staff_id',
            '{{%staff}}',
            'staff_id'
        );
        $this->createIndex('idx-interview_evaluation_preset-archived_at', '{{%interview_evaluation_preset}}', 'archived_at');

        $this->createTable('{{%interview_evaluation_change}}', [
            'change_uuid' => $this->char(60),
            'interview_evaluation_uuid' => $this->char(60)->notNull(),
            'revision' => $this->integer()->notNull(),
            'action' => $this->string(20)->notNull(),
            'staff_id' => $this->integer()->notNull(),
            'snapshot_json' => 'MEDIUMTEXT NOT NULL',
            'created_at' => $this->dateTime()->notNull(),
        ], $tableOptions);
        $this->addPrimaryKey('PK', '{{%interview_evaluation_change}}', 'change_uuid');
        $this->createIndex('idx-interview_evaluation_change-evaluation', '{{%interview_evaluation_change}}', 'interview_evaluation_uuid');
        $this->addForeignKey(
            'fk-interview_evaluation_change-evaluation',
            '{{%interview_evaluation_change}}',
            'interview_evaluation_uuid',
            '{{%interview_evaluation}}',
            'interview_evaluation_uuid',
            'CASCADE'
        );
        $this->createIndex('idx-interview_evaluation_change-staff_id', '{{%interview_evaluation_change}}', 'staff_id');
        $this->addForeignKey(
            'fk-interview_evaluation_change-staff_id',
            '{{%interview_evaluation_change}}',
            'staff_id',
            '{{%staff}}',
            'staff_id'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-interview_evaluation_change-staff_id', '{{%interview_evaluation_change}}');
        $this->dropForeignKey('fk-interview_evaluation_change-evaluation', '{{%interview_evaluation_change}}');
        $this->dropTable('{{%interview_evaluation_change}}');

        $this->dropForeignKey('fk-interview_evaluation_preset-created_by', '{{%interview_evaluation_preset}}');
        $this->dropTable('{{%interview_evaluation_preset}}');

        $this->dropForeignKey('fk-interview_evaluation-updated_by_staff_id', '{{%interview_evaluation}}');
        $this->dropIndex('idx-interview_evaluation-updated_by_staff_id', '{{%interview_evaluation}}');
        $this->dropForeignKey('fk-interview_evaluation-created_by_staff_id', '{{%interview_evaluation}}');
        $this->dropIndex('idx-interview_evaluation-created_by_staff_id', '{{%interview_evaluation}}');
        $this->dropColumn('{{%interview_evaluation}}', 'completed_by_staff_id');
        $this->dropColumn('{{%interview_evaluation}}', 'completed_at');
        $this->dropColumn('{{%interview_evaluation}}', 'updated_by_staff_id');
        $this->dropColumn('{{%interview_evaluation}}', 'created_by_staff_id');
        $this->dropColumn('{{%interview_evaluation}}', 'revision');
        $this->dropColumn('{{%interview_evaluation}}', 'answers_json');
        $this->dropColumn('{{%interview_evaluation}}', 'schema_json');
        $this->dropColumn('{{%interview_evaluation}}', 'preset_name');
        $this->dropColumn('{{%interview_evaluation}}', 'preset_uuid');
        $this->dropColumn('{{%interview_evaluation}}', 'form_status');
        $this->dropColumn('{{%interview_evaluation}}', 'evaluation_date');
        $this->dropColumn('{{%interview_evaluation}}', 'title');

        return true;
    }
}
