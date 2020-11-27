<?php

use yii\db\Migration;
use common\models\Note;

/**
 * Class m201125_141940_note
 */
class m201125_141940_note extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //to column to hide duplicate countries in future

        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('note')
            ->getColumn('candidate_id');

        if (!$columnData) {
            $this->addColumn('note','candidate_id',$this->integer(11)->null()->after('company_id'));

            $this->createIndex(
                'idx-note-candidate_id',
                'note',
                'candidate_id'
            );

            $this->addForeignKey(
                'fk-note-candidate_id',
                'note',
                'candidate_id',
                'candidate',
                'candidate_id'
            );
        }

        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('note')
            ->getColumn('request_uuid');

        if (!$columnData) {
            $this->addColumn('note','request_uuid',$this->char(60)->null()->after('candidate_id'));

            $this->createIndex(
                'idx-note-request_uuid',
                'note',
                'request_uuid'
            );

            $this->addForeignKey(
                'fk-note-request_uuid',
                'note',
                'request_uuid',
                'request',
                'request_uuid'
            );
        }

        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('note')
            ->getColumn('note_type');

        if (!$columnData) {
            $this->addColumn ('note', 'note_type', "Enum('Internal Note', 'Phone Call', 'Email', 'Meeting', 'Interview', 'Task') DEFAULT 'Internal Note' AFTER `request_uuid`");
        }

        //Move to final note

        $sql = 'select * from candidate_note';

        $notes = Yii::$app->db->createCommand ($sql)->queryAll ();

        foreach ($notes as $note) {
            $model = new Note;
            $model->candidate_id = $note['candidate_id'];
            $model->note_text = $note['note_text'];
            $model->created_by = $note['created_by'];
            $model->updated_by = $note['updated_by'];
            $model->save();
        }

        $sql = 'select ra.*, r.company_id from `request_activity` ra inner join `request` r where r.request_uuid = ra.request_uuid';

        $requestActivities = Yii::$app->db->createCommand ($sql)->queryAll ();

        foreach ($requestActivities as $requestActivity) {
            $model = new Note;
            $model->company_id = $requestActivity['company_id'];
            $model->request_uuid = $requestActivity['request_uuid'];
            $model->note_text = $requestActivity['activity_detail'];
            $model->created_by = $requestActivity['staff_id'];
            $model->updated_by = $requestActivity['staff_id'];
            $model->save();
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
        echo "m201125_141940_note cannot be reverted.\n";

        return false;
    }
    */
}
