<?php

use yii\db\Migration;

/**
 * Class m201130_103424_note_company_contact
 */
class m201130_103424_note_company_contact extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('note','contact_uuid',$this->char(60)->null()->after('request_uuid'));

        $this->createIndex(
            'idx-note-contact_uuid',
            'note',
            'contact_uuid'
        );

        $this->addForeignKey(
            'fk-note-contact_uuid',
            'note',
            'contact_uuid',
            'company_contact',
            'contact_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-note-contact_uuid','note');
        $this->dropColumn('note','contact_uuid');
    }
}
