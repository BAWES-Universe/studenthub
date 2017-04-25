<?php

use yii\db\Migration;

class m170425_114919_db_char_set extends Migration
{
    public function up()
    {
        Yii::$app->db->createCommand('ALTER TABLE admin_token CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();
        
        Yii::$app->db->createCommand('ALTER TABLE candidate_token CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();

        Yii::$app->db->createCommand('ALTER TABLE company_token CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();

        Yii::$app->db->createCommand('ALTER TABLE staff_token CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();

        Yii::$app->db->createCommand('ALTER TABLE transfer CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();

        Yii::$app->db->createCommand('ALTER TABLE transfer_candidates CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();
        
        Yii::$app->db->createCommand('ALTER TABLE invoice CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();

        Yii::$app->db->createCommand('ALTER TABLE invoice_candidates CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();

        Yii::$app->db->createCommand('ALTER TABLE bank CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();

        Yii::$app->db->createCommand('ALTER TABLE university CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci')->execute();        
    }

    public function down()
    {
    }
}
