<?php

use yii\db\Migration;

/**
 * Class m201120_072714_university
 */
class m201120_072714_university extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('university','university_data_source', $this->tinyInteger(2)->after('university_name_ar'));
        $this->addColumn('university','university_created_by', $this->integer(11)->after('university_data_source'));
        $this->addColumn('university','university_updated_by', $this->integer(11)->after('university_created_by'));
        $this->addColumn('university','university_created_at', $this->datetime()->after('university_updated_by'));
        $this->addColumn('university','university_updated_at', $this->datetime()->after('university_created_at'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201120_072714_university cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201120_072714_university cannot be reverted.\n";

        return false;
    }
    */
}
