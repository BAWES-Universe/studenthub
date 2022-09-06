<?php

use yii\db\Migration;

/**
 * Class m220818_050737_salary
 */
class m220818_050737_salary extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('staff', 'staff_salary', $this->decimal(10, 3)->after('staff_role')->defaultValue(0));
        $this->addColumn('staff', 'staff_salary_currency', $this->char(3)->after('staff_salary')->defaultValue('KWD'));

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%staff_salary}}', [
            'staff_salary_uuid' => $this->char(60),
            'staff_id' => $this->integer(11),
            'salary' => $this->decimal(10, 3),
            'salary_currency' => $this->char(3),
            'comment' => $this->string(),
            'salary_date' => $this->date()->null(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'staff_salary', 'staff_salary_uuid');

        $this->createIndex(
            'idx-staff_salary-staff_id',
            'staff_salary',
            'staff_id'
        );

        $this->addForeignKey(
            'fk-staff_salary-staff_id',
            'staff_salary',
            'staff_id',
            'staff',
            'staff_id',
            'SET NULL'
        );
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
        echo "m220818_050737_salary cannot be reverted.\n";

        return false;
    }
    */
}
