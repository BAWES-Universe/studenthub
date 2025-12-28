<?php

use common\models\CandidateWorkingDate;
use common\models\CandidateWorkingHour;
use yii\db\Migration;


/**
 * Class m240620_143332_candidate_working_date
 */
class m240620_143332_candidate_working_date extends Migration
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

        $this->createTable('{{%candidate_working_date}}', [
            'cwd_uuid' => $this->char(60),
            'candidate_id' => $this->integer(11)->notNull(),
            'store_id' => $this->integer(11)->notNull(),
            'company_id' => $this->integer(11)->notNull(),
            'date' => $this->date()->notNull(),
            "start_time" => $this->datetime()->notNull(),
            "end_time" => $this->datetime(),
            "total_time" => $this->integer(11),
            'status' => $this->smallInteger(1)->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'candidate_working_date', 'cwd_uuid');

        //candidate_id

        $this->createIndex(
            'idx-candidate_working_date-candidate_id', 'candidate_working_date', 'candidate_id'
        );

        $this->addForeignKey(
            'fk-candidate_working_date-candidate_id', 'candidate_working_date', 'candidate_id', 'candidate', 'candidate_id'
        );

        //store_id

        $this->createIndex(
            'idx-candidate_working_date-store_id', 'candidate_working_date', 'store_id'
        );

        $this->addForeignKey(
            'fk-candidate_working_date-store_id', 'candidate_working_date', 'store_id', 'store', 'store_id'
        );

        //company_id

        $this->createIndex(
            'idx-candidate_working_date-company_id', 'candidate_working_date', 'company_id'
        );

        $this->addForeignKey(
            'fk-candidate_working_date-company_id', 'candidate_working_date', 'company_id', 'company', 'company_id'
        );

        // Fixed for MySQL 9 ONLY_FULL_GROUP_BY compatibility: use raw query to get distinct combinations
        $uniqueDays = Yii::$app->db->createCommand('
            SELECT DISTINCT candidate_id, store_id, date 
            FROM candidate_working_hour
        ')->queryAll();

        foreach ($uniqueDays as $dayData) {
            $candidate_id = $dayData['candidate_id'];
            $store_id = $dayData['store_id'];
            $working_date = $dayData['date'];

            // Get store to access company_id
            $store = Yii::$app->db->createCommand('
                SELECT company_id FROM store WHERE store_id = :store_id
            ', [':store_id' => $store_id])->queryOne();

            $total_time = CandidateWorkingHour::find()
                ->andWhere([
                    "candidate_id" => $candidate_id,
                    "store_id" => $store_id,
                    "date" => $working_date,
                ])
                ->sum("total_time");

            $start_time = CandidateWorkingHour::find()
                ->andWhere([
                    "candidate_id" => $candidate_id,
                    "store_id" => $store_id,
                    "date" => $working_date,
                ])
                ->orderBy("created_at")
                ->one()
                ->start_time;

            $end_time  = CandidateWorkingHour::find()
                ->andWhere([
                    "candidate_id" => $candidate_id,
                    "store_id" => $store_id,
                    "date" => $working_date,
                ])
                ->orderBy("created_at DESC")
                ->one()
                ->end_time;

            $date = new CandidateWorkingDate;
            $date->store_id = $store_id;
            $date->company_id = $store['company_id'];
            $date->candidate_id = $candidate_id;
            $date->date = $working_date;
            $date->start_time = $start_time;
            $date->end_time = $end_time;
            $date->total_time = $total_time;
            //$this->status = $this->status;
            $date->save(false);
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
        echo "m240620_143332_candidate_working_date cannot be reverted.\n";

        return false;
    }
    */
}
