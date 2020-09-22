<?php

use yii\db\Migration;
use common\models\File;


/**
 * Class m200921_143315_file_size
 */
class m200921_143315_file_size extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $files = $this->db->createCommand('select * from file where file_type IS NULL or file_size IS NULL')->queryAll();
        
        foreach($files as $file) {
            
            if(!$file['file_size']) {
                $file['file_size'] = Yii::$app->resourceManager->getSize($file['file_s3_path']);
            }
            
            if(!$file['file_type']) {
                $file['file_type'] = Yii::$app->resourceManager->getType($file['file_s3_path']);
            }
            
            File::updateAll([
                'file_size' => $file['file_size'],
                'file_type' => $file['file_type'],
            ], [
                'file_uuid' => $file['file_uuid']
            ]);
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
        echo "m200921_143315_file_size cannot be reverted.\n";

        return false;
    }
    */
}
