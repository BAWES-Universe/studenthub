<?php

namespace company\modules\v1;

/**
 * v1 module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'company\modules\v1\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $company_id = \Yii::$app->request->headers->get('"company-id');
        if (!\Yii::$app->user->isGuest) {
            \Yii::$app->session->set('company_id',$company_id);
        }
    }

}
