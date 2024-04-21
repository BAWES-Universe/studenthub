<?php

use yii\db\Migration;

/**
 * Class m240421_081720_request_skills
 */
class m240421_081720_request_skills extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%request_skill}}', [
            'request_uuid' => $this->char(60),
            'skill' => $this->string(128)->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('request_skill_pk', 'request_skill', ['request_uuid', 'skill']);

        $this->addForeignKey(
            'fk-request_skill-request_uuid',
            'request_skill',
            'request_uuid',
            'request',
            'request_uuid',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240421_081720_request_skills cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240421_081720_request_skills cannot be reverted.\n";

        return false;
    }
    */
}
