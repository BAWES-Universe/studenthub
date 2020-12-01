<?php

use yii\db\Migration;

/**
 * Class m201201_065229_add_fulltimer_in_note
 */
class m201201_065229_add_fulltimer_in_note extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('note','fulltimer_uuid',$this->char(60)->null()->after('contact_uuid'));

        $this->createIndex(
            'idx-note-fulltimer_uuid',
            'note',
            'fulltimer_uuid'
        );

        $this->addForeignKey(
            'fk-note-fulltimer_uuid',
            'note',
            'fulltimer_uuid',
            'fulltimer',
            'fulltimer_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-note-fulltimer_uuid','note');
        $this->dropColumn('note','fulltimer_uuid');
    }
}
