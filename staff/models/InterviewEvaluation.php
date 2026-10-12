<?php

namespace staff\models;

use common\models\Staff;
use yii\db\Query;

/**
 * Staff API shape for interview evaluations.
 * The common model keeps the original fields so other applications do not inherit the form payload.
 */
class InterviewEvaluation extends \common\models\InterviewEvaluation
{
    public function fields()
    {
        $fields = parent::fields();
        if (!$this->hasAttribute('form_status')) {
            return $fields;
        }

        $fields['title'] = 'title';
        $fields['evaluation_date'] = 'evaluation_date';
        $fields['form_status'] = 'form_status';
        $fields['preset_uuid'] = 'preset_uuid';
        $fields['preset_name'] = 'preset_name';
        $fields['revision'] = 'revision';
        $fields['created_by_staff_id'] = 'created_by_staff_id';
        $fields['updated_by_staff_id'] = 'updated_by_staff_id';
        $fields['completed_at'] = 'completed_at';
        $fields['completed_by_staff_id'] = 'completed_by_staff_id';
        $fields['schema'] = function ($model) {
            return self::decodeJson($model->schema_json);
        };
        $fields['answers'] = function ($model) {
            return self::decodeJson($model->answers_json) ?: new \stdClass();
        };
        $fields['is_legacy'] = function ($model) {
            return $model->schema_json === null || $model->schema_json === '';
        };
        $fields['created_by'] = function ($model) {
            return self::staffBrief($model->created_by_staff_id);
        };
        $fields['updated_by'] = function ($model) {
            return self::staffBrief($model->updated_by_staff_id);
        };
        $fields['preset_archived'] = function ($model) {
            if (!$model->preset_uuid) {
                return false;
            }
            $archived = (new Query())
                ->select('archived_at')
                ->from('{{%interview_evaluation_preset}}')
                ->where(['preset_uuid' => $model->preset_uuid])
                ->scalar();
            return $archived === false || $archived !== null;
        };

        return $fields;
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, $recursive);
        if (array_key_exists('answers', $data) && ($data['answers'] === [] || $data['answers'] === null)) {
            $data['answers'] = new \stdClass();
        }

        return $data;
    }

    /**
     * @param mixed $json
     * @return array<string, mixed>|null
     */
    public static function decodeJson($json)
    {
        if ($json === null || $json === '') {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param mixed $staffId
     * @return array{staff_id: int, staff_name: string}|null
     */
    public static function staffBrief($staffId)
    {
        if (!$staffId) {
            return null;
        }
        $staff = (new Query())
            ->select(['staff_id', 'staff_name'])
            ->from(Staff::tableName())
            ->where(['staff_id' => (int) $staffId])
            ->one();
        if (!$staff) {
            return null;
        }

        return [
            'staff_id' => (int) $staff['staff_id'],
            'staff_name' => $staff['staff_name'],
        ];
    }
}
