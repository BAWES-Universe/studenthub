<?php

use yii\db\Migration;

/**
 * Class m210119_073444_contact_intivation_role
 */
class m210119_073444_contact_intivation_role extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('contact_invitation', 'role', $this->char(10)
            ->after('email_to_invite')->defaultValue('Owner'));

        $this->addColumn('contact_invitation', 'is_deleted', $this->tinyInteger(3)
            ->after('accepted')->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210119_073444_contact_intivation_role cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210119_073444_contact_intivation_role cannot be reverted.\n";

        return false;
    }
    */
}
