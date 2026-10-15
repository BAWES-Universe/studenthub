<?php

use yii\db\Migration;

/**
 * Staff requests for a new profile photo or an updated Civil ID.
 * Adds request rows, eligible temp-key hashes, and a notification link.
 * Does not change candidate civil columns.
 */
class m261015_140000_candidate_update_requests extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $parent = $this->db->createCommand(
                'SELECT CHARACTER_SET_NAME AS charset_name, COLLATION_NAME AS collation_name
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table
                   AND COLUMN_NAME = :column'
            )->bindValues([
                ':table' => $this->db->schema->getRawTableName('{{%candidate_notification}}'),
                ':column' => 'cn_uuid',
            ])->queryOne();
            $charset = is_array($parent) ? $parent['charset_name'] : 'utf8';
            $collation = is_array($parent) ? $parent['collation_name'] : 'utf8_unicode_ci';
            if (!preg_match('/\A[A-Za-z0-9_]+\z/', (string) $charset) || !preg_match('/\A[A-Za-z0-9_]+\z/', (string) $collation)) {
                throw new \RuntimeException('Refusing to create update-request tables with an unexpected collation.');
            }
            $tableOptions = "CHARACTER SET {$charset} COLLATE {$collation} ENGINE=InnoDB";
        }

        $this->createTable('{{%candidate_update_request}}', [
            'cur_uuid' => $this->char(60)->notNull(),
            'candidate_id' => $this->integer()->notNull(),
            'request_type' => $this->string(32)->notNull(),
            'status' => $this->string(16)->notNull(),
            'staff_id' => $this->integer()->notNull(),
            'requested_at' => $this->dateTime()->notNull(),
            'submitted_at' => $this->dateTime()->null(),
            'baseline_photo_sha256' => $this->char(64)->null(),
            'baseline_front_sha256' => $this->char(64)->null(),
            'baseline_back_sha256' => $this->char(64)->null(),
            'staged_front_key' => $this->string(255)->null(),
            'staged_back_key' => $this->string(255)->null(),
            'staged_front_source_sha256' => $this->char(64)->null(),
            'staged_back_source_sha256' => $this->char(64)->null(),
            'staging_revision' => $this->integer()->notNull()->defaultValue(0),
            'open_slot' => $this->string(64)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-candidate_update_request', '{{%candidate_update_request}}', 'cur_uuid');
        $this->createIndex('idx-candidate_update_request-candidate', '{{%candidate_update_request}}', 'candidate_id');
        $this->createIndex('uidx-candidate_update_request-open_slot', '{{%candidate_update_request}}', 'open_slot', true);
        $this->addForeignKey(
            'fk-candidate_update_request-candidate_id',
            '{{%candidate_update_request}}',
            'candidate_id',
            '{{%candidate}}',
            'candidate_id'
        );
        $this->addForeignKey(
            'fk-candidate_update_request-staff_id',
            '{{%candidate_update_request}}',
            'staff_id',
            '{{%staff}}',
            'staff_id'
        );

        $this->createTable('{{%candidate_update_request_upload}}', [
            'id' => $this->primaryKey(),
            'cur_uuid' => $this->char(60)->notNull(),
            'key_sha256' => $this->char(64)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ], $tableOptions);
        $this->createIndex(
            'idx-candidate_update_request_upload-request',
            '{{%candidate_update_request_upload}}',
            'cur_uuid'
        );
        $this->addForeignKey(
            'fk-candidate_update_request_upload-request',
            '{{%candidate_update_request_upload}}',
            'cur_uuid',
            '{{%candidate_update_request}}',
            'cur_uuid'
        );

        $this->addColumn('{{%candidate_notification}}', 'update_request_uuid', $this->char(60)->null());
        $this->createIndex(
            'idx-candidate_notification-update_request_uuid',
            '{{%candidate_notification}}',
            'update_request_uuid'
        );
        $this->addForeignKey(
            'fk-candidate_notification-update_request_uuid',
            '{{%candidate_notification}}',
            'update_request_uuid',
            '{{%candidate_update_request}}',
            'cur_uuid'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-candidate_notification-update_request_uuid', '{{%candidate_notification}}');
        $this->dropIndex('idx-candidate_notification-update_request_uuid', '{{%candidate_notification}}');
        $this->dropColumn('{{%candidate_notification}}', 'update_request_uuid');

        $this->dropForeignKey('fk-candidate_update_request_upload-request', '{{%candidate_update_request_upload}}');
        $this->dropTable('{{%candidate_update_request_upload}}');

        $this->dropForeignKey('fk-candidate_update_request-staff_id', '{{%candidate_update_request}}');
        $this->dropForeignKey('fk-candidate_update_request-candidate_id', '{{%candidate_update_request}}');
        $this->dropTable('{{%candidate_update_request}}');
    }
}
