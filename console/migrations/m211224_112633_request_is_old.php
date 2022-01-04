<?php

use yii\db\Migration;

/**
 * Class m211224_112633_request_is_old
 */
class m211224_112633_request_is_old extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('request')
            ->getColumn('is_old');

        if (!$columnData) {
            $this->addColumn('request', 'is_old', $this->tinyInteger(1)->defaultValue(0)->after('request_priority'));
        }

        // update all exist request as old one
        Yii::$app->db->createCommand ("UPDATE `request` SET `is_old` = '1'")->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('request','is_old');
    }
}