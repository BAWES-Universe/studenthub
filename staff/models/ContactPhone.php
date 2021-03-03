<?php

namespace staff\models;


class ContactPhone extends \common\models\ContactPhone
 {
                      /**
                       * @return \yii\db\ActiveQuery
                       */
                      public function getContact($modelClass = "\staff\models\Contact")
                      {
                          return parent::getContact($modelClass);
                      }
                  }
