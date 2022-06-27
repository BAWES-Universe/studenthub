<?php

namespace company\modules\v1;

use Segment\Segment;

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

        $lang = \Yii::$app->request->headers->get('language');

        if ($lang && $lang != \Yii::$app->language)
        {
            \Yii::$app->language = $lang;
        }

        parent::init();

        if(YII_ENV == 'prod') {

            Segment::init('WZc7uvfkM1uhsjT1Eie6PONXFZK3ME15');

            if(!Yii::$app->user->isGuest)
            {
                $user = Yii::$app->user->identity;

                Segment::identify([Yii::$app->user->getId(), [
                    "name" => $user->contact_name,
                    "email" => $user->contact_email
                ]]);
            }
        }
    }
}
