<?php

use yii\db\Migration;

/**
 * Class m231129_080008_staff_notification
 */
class m231129_080008_staff_notification extends Migration
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

        $this->createTable('staff_notification', [
            "sn_uuid"=> $this->char(60)->notNull(), // used as reference id
            "staff_id" => $this->integer(11),
            "permission" => $this->string(100),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'staff_notification', 'sn_uuid');

        // creates index for column `staff_id`
        $this->createIndex(
            'idx-staff_notification-staff_id',
            'staff_notification',
            'staff_id'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-staff_notification-staff_id',
            'staff_notification',
            'staff_id',
            'staff',
            'staff_id'
        );

        $permissions = [
            "transfer-failed",
            "morning-report",
            "daily-attendance-notification",
            "request-updates",
            "new-requests",
        ];

        $staffs = \common\models\Staff::find()->all();

        foreach ($staffs as $staff)
        {
            foreach ($permissions as $permission) {
                $model = new \common\models\StaffNotification();
                $model->staff_id = $staff->staff_id;
                $model->permission = $permission;
                $model->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231129_080008_staff_notification cannot be reverted.\n";

        return false;
    }
    */
}
