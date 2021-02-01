<?php

namespace staff\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

class CompanyContact extends \common\models\CompanyContact
{

    public function fields()
    {
        $fields = parent::fields();
        unset(
            $fields['created_at'],$fields['updated_at'],
            $fields['created_by'],$fields['updated_by']
        );
        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\staff\models\Contact")
    {
        return parent::getContact($modelClass);
    }
}
