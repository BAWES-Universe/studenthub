<?php

use yii\db\Migration;

/**
 * Class m200623_130939_store_candidates
 */
class m200623_130939_store_candidates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('store', 'store_total_candidates', $this->integer(5)->after('store_name')->defaultValue(0));
        
        $stores = $this->db->createCommand('select * from store where deleted=0')->queryAll();
        
        foreach($stores as $store) {
            $candidates = $this->db->createCommand('select count(*) from candidate where store_id="'.$store['store_id'].'"')->queryScalar();
            
            $this->db->createCommand('update store SET store_total_candidates="'.$candidates.'" WHERE store_id="'.$store['store_id'].'"')->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200623_130939_store_candidates cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200623_130939_store_candidates cannot be reverted.\n";

        return false;
    }
    */
}
