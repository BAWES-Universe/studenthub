<?php

use yii\db\Migration;

/**
 * Class m201106_060223_update_note_table
 */
class m201106_060223_update_note_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        $this->addColumn('candidate_note','created_by',$this->integer()->after('note_text'));
        $this->addColumn('candidate_note','updated_by',$this->integer()->after('created_by'));
        Yii::$app->db->createCommand('update `candidate_note` set `created_by`=`staff_id`, `updated_by`=`staff_id` where 1')->execute();


        // creates index for column `created_by`
        $this->createIndex(
            'idx-candidate_note-created_by',
            'candidate_note',
            'created_by'
        );

        // creates index for column `updated_by`
        $this->createIndex(
            'idx-candidate_note-updated_by',
            'candidate_note',
            'updated_by'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-candidate_note-created_by',
            'candidate_note',
            'created_by',
            'staff',
            'staff_id'

        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-candidate_note-updated_by',
            'candidate_note',
            'updated_by',
            'staff',
            'staff_id'

        );

        $this->dropForeignKey('fk-candidate_note-staff_id','candidate_note');
        $this->dropColumn('candidate_note','staff_id');



        $this->addColumn('note','created_by',$this->integer()->after('note_text'));
        $this->addColumn('note','updated_by',$this->integer()->after('created_by'));
        Yii::$app->db->createCommand('update `note` set `created_by`=`staff_id`, `updated_by`=`staff_id` where 1')->execute();


        // creates index for column `created_by`
        $this->createIndex(
            'idx-note-created_by',
            'note',
            'created_by'
        );

        // creates index for column `updated_by`
        $this->createIndex(
            'idx-note-updated_by',
            'note',
            'updated_by'
        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-note-created_by',
            'note',
            'created_by',
            'staff',
            'staff_id'

        );

        // add foreign key for table `staff_id`
        $this->addForeignKey(
            'fk-note-updated_by',
            'note',
            'updated_by',
            'staff',
            'staff_id'

        );

        $this->dropForeignKey('fk-note-staff_id','note');
        $this->dropColumn('note','staff_id');
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201106_060223_update_note_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201106_060223_update_note_table cannot be reverted.\n";

        return false;
    }
    */
}
