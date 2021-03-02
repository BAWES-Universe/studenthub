<?php

namespace company\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;


/**
 * This is the model class for table "university".
 *
 * @property integer $university_id
 * @property string $university_name_en
 * @property string $university_name_ar
 * @property integer $university_data_source
 * @property string $university_created_by
 * @property string $university_updated_by
 * @property string $university_created_at
 * @property string $university_updated_at
 * @property integer $deleted
 *
 * @property Candidate[] $candidates
 */
class University extends \common\models\University
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['deleted'],$fields['total_candidates']);
        return $fields;
    }    

}
