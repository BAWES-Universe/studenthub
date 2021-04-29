<?php

namespace staff\tests;

use Yii;
use common\models\Fulltimer;
use staff\tests\FunctionalTester;
use common\models\Area;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\FulltimerFixture;
use Codeception\Util\HttpCode;


class FulltimerCest
{
    public $token;
    public $fulltimer;

    public function _fixtures()
    {
        return [
        	'staffToken' => StaffTokenFixture::className(),
            'fulltimer' => FulltimerFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()
            ->one()
            ->token_value;
        
        $this->fulltimer = Fulltimer::find()->one();

        $I->amBearerAuthenticated($this->token);
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate fulltimer api response for listing');
        $I->sendGET('v1/fulltimers');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * View fulltimer detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo('Validate fulltimer api to view note detail');
        $I->sendGET('v1/fulltimers/' . $this->fulltimer->fulltimer_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Try to create new fulltimer
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $area = Area::find()->one();

        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.pdf',
            [],
            codecept_data_dir() . 'files/sample.pdf',
            'application/pdf'
        );
        
        $I->wantTo('create a fulltimer via API');
        $I->sendPOST(
            'v1/fulltimers',
            [
                'nationality_id' => 1,
                'area_uuid' => $area->area_uuid,
                'country_id' => 1,
                'latitude' => 70,
                'longitude' => 70,
                'name' => 'Shri Hari',
                'phone' => '4342143234',
                'email' => 'test@locao.com',
                'current_salary' => '1111',
                'expected_salary' => '222',
                'pdf_cv' => basename($response['ObjectURL']),
                'tags' => [
                    [
                        'tag' => 'Tag 1',
                    ],
                    [
                        'tag' => 'Tag 2',
                    ]
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $area = Area::find()->one();

        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.pdf',
            [],
            codecept_data_dir() . 'files/sample.pdf',
            'application/pdf'
        );

        $I->wantTo('update a fulltimer via API');
        $I->sendPATCH(
            'v1/fulltimers/' . $this->fulltimer->fulltimer_uuid,
            [
                'nationality_id' => 1,
                'area_uuid' => $area->area_uuid,
                'country_id' => 1,
                'latitude' => 70,
                'longitude' => 70,
                'name' => 'Shri Hari',
                'phone' => '4342143234',
                'current_salary' => '1111',
                'expected_salary' => '222',
                'email' => 'test@locao.com',
                'pdf_cv' => basename($response['ObjectURL']),
                'tags' => [
                    [
                        'tag' => 'Tag 1',
                    ],
                    [
                        'tag' => 'Tag 2',
                    ]
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete fulltimer via API');
        $I->sendDelete('v1/fulltimers/' . $this->fulltimer->fulltimer_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}

