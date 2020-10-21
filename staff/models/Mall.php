<?php

namespace staff\models;

/**
 * This is the model class for table "mall".
 *
 * @property string $mall_uuid
 * @property string $mall_name_en
 * @property string $mall_name_ar
 * @property string $mall_created_datetime
 * @property string $mall_updated_datetime
 *
 * @property Store[] $stores
 */
class Mall extends \common\models\Mall
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        $field = parent::fields();
        unset($field['mall_updated_datetime'],$field['mall_created_datetime']);
        return $field;
    }
}
