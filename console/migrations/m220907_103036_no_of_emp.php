<?php

use yii\db\Migration;
use common\models\Request;
use common\models\Story;

/**
 * Class m220907_103036_no_of_emp
 */
class m220907_103036_no_of_emp extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('story', 'number_of_employees', $this->smallInteger(6)->after('staff_id'));

        $requests = \common\models\Request::find()->all();

        foreach ($requests as $request)
        {
            $total = 0;

            foreach ($request->stories as $story)
            {
                $total += $request->no_of_employees_per_story;

                $story->number_of_employees = $total <= $request->request_number_of_employees ?
                    $request->no_of_employees_per_story: $request->no_of_employees_per_story - ($total - $request->request_number_of_employees);

                //avoid after save/before save

                Story::updateAll(['number_of_employees' => $story->number_of_employees], [
                    'story_uuid' => $story->story_uuid
                ]);
            }
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
        echo "m220907_103036_no_of_emp cannot be reverted.\n";

        return false;
    }
    */
}
