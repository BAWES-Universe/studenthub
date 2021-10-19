<?php

use yii\db\Migration;

/**
 * Class m211019_093500_fulltimer_filter
 */
class m211019_093500_fulltimer_filter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

                $this->addColumn (
                    'fulltimer',
                    'university_id',
                    $this->integer (11)->after ('country_id')
                );

                // creates index for column `university_id`
                $this->createIndex(
                    'idx-fulltimer-university_id',
                    'fulltimer',
                    'university_id'
                );

                // add foreign key for table `university`
                $this->addForeignKey(
                    'fk-fulltimer-university_id',
                    'fulltimer',
                    'university_id',
                    'university',
                    'university_id',
                    'SET NULL'
                );

                $this->addColumn (
                    'fulltimer',
                    'fulltimer_employed',
                    $this->boolean ()->after ('university_id')
                );

                $this->addColumn (
                    'fulltimer',
                    'fulltimer_gender',
                    $this->tinyInteger (1)->after ('fulltimer_employed')
                );

                $this->addColumn (
                    'fulltimer',
                    'fulltimer_birth_date',
                    $this->date ()->after ('fulltimer_gender')
                );

                $this->addColumn (
                    'fulltimer',
                    'fulltimer_driving_license',
                    $this->boolean()->after ('fulltimer_birth_date')
                );
        
        //fulltimer_skill

        $this->createTable('{{%fulltimer_skill}}', [
            'fulltimer_skill_id' => $this->primaryKey(),
            'fulltimer_uuid' => $this->char(60),
            'skill' => $this->string(128)->notNull(),
            'fulltimer_skill_created_at' => $this->dateTime(),
            'deleted' => $this->smallInteger(1)->defaultValue(0)->notNull()
        ], $tableOptions);

        $this->createIndex(
            'idx-fulltimer_skill-fulltimer_uuid',
            'fulltimer_skill',
            'fulltimer_uuid'
        );

        $this->addForeignKey(
            'fk-fulltimer_skill-fulltimer_uuid',
            'fulltimer_skill',
            'fulltimer_uuid',
            'fulltimer',
            'fulltimer_uuid',
            'CASCADE'
        );

        //fulltimer_experience

        $this->createTable('{{%fulltimer_experience}}', [
            'fulltimer_experience_id' => $this->primaryKey(),
            'fulltimer_uuid' => $this->char(60),
            'experience' => $this->string(128)->notNull(),
            'fulltimer_experience_created_at' => $this->dateTime(),
            'deleted' => $this->smallInteger(1)->defaultValue(0)->notNull()
        ], $tableOptions);

        $this->createIndex(
            'idx-fulltimer_experience-fulltimer_uuid',
            'fulltimer_experience',
            'fulltimer_uuid'
        );

        $this->addForeignKey(
            'fk-fulltimer_experience-fulltimer_uuid',
            'fulltimer_experience',
            'fulltimer_uuid',
            'fulltimer',
            'fulltimer_uuid',
            'CASCADE'
        );

        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211019_093500_fulltimer_filter cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211019_093500_fulltimer_filter cannot be reverted.\n";

        return false;
    }
    */
}
