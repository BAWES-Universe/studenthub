<?php

namespace candidate\modules\v1;

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
    }

}
