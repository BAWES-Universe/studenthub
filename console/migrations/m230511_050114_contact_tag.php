<?php

use yii\db\Migration;

/**
 * Class m230511_050114_contact_tag
 */
class m230511_050114_contact_tag extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('contact', 'contact_status',
            $this->tinyInteger (1)
                ->after ('contact_receive_suggestions')
                ->defaultValue (10));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230511_050114_contact_tag cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230511_050114_contact_tag cannot be reverted.\n";

        return false;
    }
    */
}
