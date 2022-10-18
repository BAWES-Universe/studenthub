<?php

use yii\db\Migration;

/**
 * Class m221017_131312_permission
 */
class m221017_131312_permission extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('permission_section', [
            "permission_uuid" => $this->char(60),
            'section_name' => $this->string(),
            'created_at' => $this->datetime()->notNull(),
        ], $tableOptions);
        $this->addPrimaryKey('PK', 'permission_section', 'permission_uuid');

        $this->createTable('permission_sub_section', [
            "permission_sub_section_uuid" => $this->char(60),
            'sub_section_name' => $this->string(),
            'sub_section_slug' => $this->string(),
            'permission_uuid' => $this->char(60),
            'created_at' => $this->datetime()->notNull(),
        ], $tableOptions);
        $this->addPrimaryKey('PK', 'permission_sub_section', 'permission_sub_section_uuid');


        $this->createIndex(
            'idx-permission_sub_section-permission_uuid',
            'permission_sub_section',
            'permission_uuid'
        );

        // add foreign key for table `request_uuid`
        $this->addForeignKey(
            'fk-permission_sub_section-permission_uuid',
            'permission_sub_section',
            'permission_uuid',
            'permission_section',
            'permission_uuid'
        );


        $this->createTable('permission_user', [
            "permission_user_uuid" => $this->char(60),
            'admin_id' => $this->integer()->null(),
            'staff_id' => $this->integer()->null(),
            'permission_sub_section_uuid' => $this->char(60),
            'created_at' => $this->datetime()->notNull(),
        ], $tableOptions);
        $this->addPrimaryKey('PK', 'permission_user', 'permission_user_uuid');

        $this->createIndex(
            'idx-permission_user-permission_sub_section_uuid',
            'permission_user',
            'permission_sub_section_uuid'
        );

        $this->addForeignKey(
            'fk-permission_user-permission_sub_section_uuid',
            'permission_user',
            'permission_sub_section_uuid',
            'permission_sub_section',
            'permission_sub_section_uuid'
        );

        $this->createIndex(
            'idx-permission_user-admin_id',
            'permission_user',
            'admin_id'
        );

        $this->addForeignKey(
            'fk-permission_user-admin_id',
            'permission_user',
            'admin_id',
            'admin',
            'admin_id'
        );

        $this->createIndex(
            'idx-permission_user-staff_id',
            'permission_user',
            'staff_id'
        );

        $this->addForeignKey(
            'fk-permission_user-staff_id',
            'permission_user',
            'staff_id',
            'staff',
            'staff_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('permission_section');
        $this->dropTable('permission_sub_section');
        $this->dropTable('permission_user');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221017_131312_permission cannot be reverted.\n";

        return false;
    }
    */
}
