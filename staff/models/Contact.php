<?php

namespace staff\models;

class Contact extends \common\models\Contact
{
    /**
     * @inheritdoc
     * shifted to contact model
     */
//    public function fields()
//    {
//        return array_merge(parent::fields(),
//            [
//                'role' => function($model) {
//                   return $model->getCompanyContacts()->one()->role;
//                }
//            ]
//        );
//    }
}
