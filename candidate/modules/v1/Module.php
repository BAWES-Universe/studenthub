<?php

namespace candidate\modules\v1;

use Segment\Segment;
use Yii;

/**
 * v1 module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'candidate\modules\v1\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $lang = Yii::$app->request->headers->get('language');

        if ($lang && $lang != Yii::$app->language)
        {
            Yii::$app->language = $lang;
        }

        if(YII_ENV == 'prod') {

            if(!Yii::$app->user->isGuest)
            {
                $user = Yii::$app->user->identity;

                Yii::$app->eventManager->setUser(Yii::$app->user->getId(), [
                    "name" => $user->candidate_name,
                    "email" => $user->candidate_email
                ]);
            }
        }
    }
}
