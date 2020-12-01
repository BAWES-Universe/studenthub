<?php
namespace common\tests\models;

use Yii;
use common\models\Area;
use common\models\Fulltimer;
use common\fixtures\FulltimerFixture;
use common\fixtures\AreaFixture;
use common\fixtures\CountryFixture;
use Codeception\Specify;


class FulltimerTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures(){
        return [
            'fulltimer' => FulltimerFixture::className(),
            'area' => AreaFixture::className(),
            'country' => CountryFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Check fulltimer tags loaded',
                Fulltimer::find()->one()
            )->notNull();
        });

        $this->specify('Admin model fields validation', function () {
            $admin = new Fulltimer();
            
            expect('should not accept empty nationality_id', $admin->validate(['nationality_id']))->false();
            expect('should not accept empty country_id', $admin->validate(['country_id']))->false();
            expect('should not accept empty fulltimer_area_uuid', $admin->validate(['fulltimer_area_uuid']))->false();
            expect('should not accept empty fulltimer_latitude', $admin->validate(['fulltimer_latitude']))->false();
            expect('should not accept empty fulltimer_longitude', $admin->validate(['fulltimer_longitude']))->false();
            expect('should not accept empty fulltimer_name', $admin->validate(['fulltimer_name']))->false();
            expect('should not accept empty fulltimer_phone', $admin->validate(['fulltimer_phone']))->false();
            expect('should not accept empty fulltimer_email', $admin->validate(['fulltimer_email']))->false();
            expect('should not accept empty fulltimer_pdf_cv', $admin->validate(['fulltimer_pdf_cv']))->false();

        });
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        $this->specify('Create New Fulltimer', function () {
            $response = Yii::$app->temporaryBucketResourceManager->save(
                null,
                'sample.pdf',
                [],
                codecept_data_dir() . 'files/sample.pdf',
                'application/pdf'
            );

            $area = Area::find()->one();

            $model = new Fulltimer();
            $model->nationality_id = 1;
            $model->country_id = 1;
            $model->fulltimer_area_uuid = $area->area_uuid;
            $model->fulltimer_latitude = 1;
            $model->fulltimer_longitude = 1;
            $model->fulltimer_name = 'Test';
            $model->fulltimer_phone = '874957235';
            $model->fulltimer_email = 'test@localhost.com';
            $model->fulltimer_pdf_cv = basename($response['ObjectURL']);

            expect('Created successfully', $model->save())->true();
        });

        $this->specify('Update fulltimer', function() {
            $area = Area::find()->one();

            $model = Fulltimer::find()->one();
            $model->fulltimer_area_uuid = $area->area_uuid;
            $model->fulltimer_name = 'Matro';
            $model->validate();

            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['fulltimer_name' => 'Matro']))->notNull();
        });
    }
}
