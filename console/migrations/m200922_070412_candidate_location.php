<?php

use yii\db\Migration;

/**
 * Class m200922_070412_candidate_location
 */
class m200922_070412_candidate_location extends Migration
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
        
        $this->createTable('{{%area}}', [
            'area_uuid' => $this->string(60),
            'area_name_en' => $this->string()->notNull(),
            'area_name_ar' => $this->string()->notNull(),
            'area_created_at' => $this->dateTime(),
            'area_updated_at' => $this->dateTime(),
            'area_created_by' => $this->string(60),
            'area_updated_by' => $this->string(60),
        ], $tableOptions);  
        
        $this->addPrimaryKey('PK', 'area', 'area_uuid'); 
        
        $this->addColumn('candidate', 'candidate_area_uuid', $this->string(60)->after('candidate_address_line1'));
        $this->addColumn('candidate', 'candidate_latitude', $this->decimal(9,6)->after('candidate_area_uuid'));
        $this->addColumn('candidate', 'candidate_longitude', $this->decimal(9,6)->after('candidate_latitude'));

        $this->createIndex(
            'idx-candidate-candidate_area_uuid',
            'candidate',
            'candidate_area_uuid'
        );
        
        $this->addForeignKey(
            'fk-candidate-candidate_area_uuid',
            'candidate',
            'candidate_area_uuid',
            'area',
            'area_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    { 
        $this->dropIndex(
            'idx-candidate-candidate_area_uuid',
            'candidate'
        );
        
        $this->dropForeignKey(
            'fk-candidate-candidate_area_uuid',
            'candidate'
        );

        $this->removeColumn('candidate', 'candidate_area_uuid');
        $this->removeColumn('candidate', 'candidate_latitude');
        $this->removeColumn('candidate', 'candidate_longitude');

        $this->dropTable('area');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200922_070412_candidate_location cannot be reverted.\n";

        return false;
    }
    */
}
