<?php

namespace common\models\query;


class SuggestionQuery extends \yii\db\ActiveQuery
{
    public function filterNotMailed()
    {
        return $this->andWhere(['suggestion.mail_to_company' => 0]);
    }
}