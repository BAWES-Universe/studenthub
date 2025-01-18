<?php

namespace staff\tests;

use Codeception\Util\HttpCode;
use common\fixtures\StoryFixture;
use common\fixtures\StaffTokenFixture;
use common\fixtures\RequestFixture;
use common\models\StaffToken;
use common\models\Story;
use staff\models\Candidate;


class StoryCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::class,
            'story' => StoryFixture::class,
            'request' => RequestFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I){}

    /**
     * Try to list
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('list story via API');
        $I->sendGET('v1/story/list');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * Try to list active story
     * @param FunctionalTester $I
     */
    public function tryToListActive(FunctionalTester $I)
    {
        $I->wantTo('list active story via API');
        $I->sendGET('v1/story/active-story');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * Try to list old story
     * @param FunctionalTester $I
     */
    public function tryToListOld(FunctionalTester $I)
    {
        $I->wantTo('list old stories via API');
        $I->sendGET('v1/story/all-old-stories');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * try to get story details
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $story = Story::find()->one();

        $I->wantTo('View story detail via API');
        $I->sendGET('v1/story/' . $story->story_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * try to update story status
     * @param FunctionalTester $I
     */
    public function tryToUpdateStatus(FunctionalTester $I)
    {
        $story = Story::find()->one();

        $I->wantTo('Update story status via API');
        $I->sendPOST('v1/story/change-story-status', [
            'story_uuid' => $story->story_uuid,
            'status' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}