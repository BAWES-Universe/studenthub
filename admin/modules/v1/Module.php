<?php

namespace admin\modules\v1;

use Yii;
use Segment\Segment;

/**
 * v1 module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'admin\modules\v1\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if(YII_ENV == 'prod') {

            Segment::init('WZc7uvfkM1uhsjT1Eie6PONXFZK3ME15');

            if(!Yii::$app->user->isGuest)
            {
                $user = Yii::$app->user->identity;

                Segment::identify([Yii::$app->user->getId(), [
                    "name" => $user->admin_name,
                    "email" => $user->admin_email
                ]]);
            }
        }
    }

}
