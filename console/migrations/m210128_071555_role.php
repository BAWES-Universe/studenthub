<?php

use yii\db\Migration;

/**
 * Class m210128_071555_role
 */
class m210128_071555_role extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('company_contact', 'allow_access', $this->boolean ()->after('role')->defaultValue (0));

        Yii::$app->db->createCommand('update company_contact set allow_access="1" 
                    where role in ("Owner", "HR")')->execute();

        $this->dropColumn ('company_contact', 'role');

        //move position

        $this->addColumn ('company_contact', 'contact_position', $this->string (100)->after ('company_id'));

        $contacts = Yii::$app->db->createCommand('select * from contact')->queryAll();

        foreach($contacts as $contact) {
            Yii::$app->db->createCommand('update company_contact set contact_position="'.$contact['contact_position'].'" 
                    where contact_uuid="'.$contact['contact_uuid'].'"')->execute();
        }

        $this->dropColumn ('contact', 'contact_position');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210128_071555_role cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210128_071555_role cannot be reverted.\n";

        return false;
    }
    */
}
