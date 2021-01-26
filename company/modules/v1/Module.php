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
        $company_id = \Yii::$app->request->headers->get('Company-Id');
        if (!\Yii::$app->user->isGuest) {
            \Yii::$app->companyManager->setCompanyId($company_id);
        }
        parent::init();
    }
}
