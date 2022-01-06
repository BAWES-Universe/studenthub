<?php

namespace common\tests;

use common\fixtures\CandidateFixture;
use common\models\CandidateVideoLog;


class CandidateVideoLogTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidate' => CandidateFixture::className ()
        ];
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testValidate()
    {
        $model = new CandidateVideoLog();

        $model->candidate_id = '123123123';
        expect ('Invalid candidate id', $model->validate (['candidate_id']))->false ();
    }
}
