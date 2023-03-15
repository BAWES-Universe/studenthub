<?php

namespace staff\modules\v1;

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
    public $controllerNamespace = 'staff\modules\v1\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if(YII_ENV == 'prod') {

            if(!Yii::$app->user->isGuest)
            {
                $user = Yii::$app->user->identity;

                Yii::$app->eventManager->setUser(Yii::$app->user->getId(), [
                    "name" => $user->staff_name,
                    "email" => $user->staff_email
                ]);
            }
        }
    }
}
