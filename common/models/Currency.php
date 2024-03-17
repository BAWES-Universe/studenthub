<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "currency".
 *
 * @property int $currency_id
 * @property string $title
 * @property string $code
 * @property string $currency_symbol
 * @property string $rate
 * @property integer $decimal_place
 * @property integer $sort_order
 * @property integer $status
 * @property string $datetime
 *
 * @property Restaurant[] $restaurants
 */
class Currency extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'currency';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['title', 'code', 'rate'], 'required'],
            [['rate', 'decimal_place', 'status'], 'number'],
            [['sort_order'], 'number', 'max' => 100],
            [['title', 'code'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields() {
        $fields = parent::fields();

        $fields['status'] = function($model) {
            return (bool) $model->status;
        };

        $fields['symbol'] = function($model) {
            return $this->currencySymbol($model->code);
        };

        $fields['title'] = function($model) {
            return mb_convert_encoding($model->title, 'UTF-8', 'UTF-8');
        };

        $fields['currency_symbol'] = function($model) {
            return mb_convert_encoding($model->currency_symbol, 'UTF-8', 'UTF-8');
        };

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields() {
        return array_merge(
            parent::extraFields(),
            [
                'sign'
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'title' => Yii::t('app', 'Title'),
            'code' => Yii::t('app', 'Code'),
            'rate' => Yii::t('app', 'Rate'),
            'decimal_place' => Yii::t('app', 'Decimal Place'),
            'sort_order' => Yii::t('app', 'Sort Order'),
            'status' => Yii::t('app', 'Status'),
            'datetime' => Yii::t('app', 'Date Time'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className (),
                'createdAtAttribute' => null,
                'updatedAtAttribute' => 'datetime',
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    /**
     * update currency rates
     * @param bool $useTransaction
     * @return string
     * @throws \yii\db\Exception
     */
    public static function getDataFromApi($useTransaction = true) {

        $api_key = Yii::$app->params['currencylayer_api_key'];

        $response = file_get_contents('http://apilayer.net/api/live?access_key=' . $api_key . '&source=USD');

        $data = json_decode($response);

        $insertData = [];
        $currentData = include(Yii::getAlias('@common/fixtures/data/currency.php'));

        $arrSortOrder = \yii\helpers\ArrayHelper::map($currentData, 'code', 'sort_order');
        $arrCurrencyName = \yii\helpers\ArrayHelper::map($currentData, 'code', 'title');
        $arrCurrencySymbol = \yii\helpers\ArrayHelper::map($currentData, 'code', 'currency_symbol');

        if (isset($data->quotes)) {

            if($useTransaction)
                $transaction = Yii::$app->db->beginTransaction();

            foreach ($data->quotes as $label => $rate) {

                $currency = substr($label, 3);

                if (strlen($currency) > 3) {
                    continue;
                }

                $model = Currency::findOne(['code' => $currency]);

                if(!$model) {
                    $model = new Currency();
                    $model->code = $currency;
                }

                $model->title = isset($arrCurrencyName[$currency]) ? $arrCurrencyName[$currency] : $currency;
                $model->currency_symbol = isset($arrCurrencySymbol[$currency]) ? $arrCurrencySymbol[$currency] : null;
                $model->rate = $rate;

                if(!$model->save()) {

                    if($useTransaction)
                        $transaction->rollBack();

                    return [
                        "errors" => $model->errors,
                        "values" => $model->attributes
                    ];
                }
            }

            if ($useTransaction)
                $transaction->commit();

            return "Currency record updated!";
        } else {
            return "no record found";
        }
    }

    public function getSign()
    {
        $arr = [
            'AUD' => '$',
            'BGN' => 'лв',
            'BRL' => 'R$',
            'CAD' => '$',
            'CHF' => "CHF",
            'CNY' => '¥',
            'CZK' => 'Kč',
            'DKK' => 'kr',
            'EUR' => '€',
            'GBP' => '£',
            'HKD' => '$',
            'HRK' => 'kn',
            'HUF' => 'Ft',
            "IDR" => 'Rp',
            'ILS' => '₪',
            'INR' => '₹',
            'JPY' => '¥',
            'KRW' => '₩',
            'MXN' => '$',
            'MYR' => 'RM',
            'NOK' => 'kr',
            'NZD' => '$',
            'PHP' => '₱',
            'PLN' => 'zł',
            'RON' => 'lei',
            'RUB' => '₽',
            'SEK' => 'kr',
            'SGD' => '$',
            'THB' => '฿',
            'TRY' => '₺',
            'USD' => '$',
            'ZAR' => 'R'
        ];

        return isset($arr[$this->code])? $arr[$this->code]: $this->code;
    }

    /**
     * Return currency symbol by currency code
     * @param string $currency_code
     * @return httpcode
     */
    public function currencySymbol($currency_code) {

        switch ($currency_code) {

            case( 'AUD' ):
                $cur = "&#36;";
                break;

            case( 'BGN' ):
                $cur = "&#1083;&#1074;";
                break;

            case( 'BRL' ):
                $cur = "R&#36;";
                break;

            case( 'CAD' ):
                $cur = "C&#36;";
                break;

            case( 'CHF' ):
                $cur = "&#165;";
                break;

            case( 'CZK' ):
                $cur = "K&#269;";
                break;

            case( 'DKK' ):
                $cur = "kr";
                break;

            case( 'EUR' ):
                $cur = "&#8364;";
                break;

            case( 'GBP' ):
                $cur = "&#163;";
                break;

            case( 'HKD' ):
                $cur = "&#36;";
                break;

            case( 'HRK' ):
                $cur = "kn";
                break;

            case( 'HUF' ):
                $cur = "Ft";
                break;

            case( 'IDR' ):
                $cur = "Rp";
                break;

            case( 'ILS' ):
                $cur = "&#8362;";
                break;

            case( 'INR' ):
                $cur = "&#8377;";
                break;

            case( 'JPY' ):
                $cur = "&#165;";
                break;

            case( 'KRW' ):
                $cur = "&#8361;";
                break;

            case( 'LTL' ):
                $cur = "Lt";
                break;

            case( 'LVL' ):
                $cur = "Ls";
                break;

            case( 'MXN' ):
                $cur = "&#36;";
                break;

            case( 'MYR' ):
                $cur = "RM";
                break;

            case( 'NOK' ):
                $cur = "kr";
                break;

            case( 'NZD' ):
                $cur = "&#36;";
                break;

            case( 'PHP' ):
                $cur = "&#8369;";
                break;

            case( 'PLN' ):
                $cur = "&#122;&#322;";
                break;

            case( 'RON' ):
                $cur = "&#108;&#101;&#105;";
                break;

            case( 'RUB' ):
                $cur = "₽";
                break;

            case( 'SEK' ):
                $cur = "kr";
                break;

            case( 'SGD' ):
                $cur = "&#36;";
                break;

            case( 'THB' ):
                $cur = "&#3647;";
                break;

            case( 'TRY' ):
                $cur = "&#8356;";
                break;

            case( 'USD' ):
                $cur = "&#36;";
                break;

            case( 'ZAR' ):
                $cur = "R";
                break;

            case( 'CNY' ):
                $cur = "元";
                break;
            default:
                $cur = $currency_code;
                break;
        }
        return $cur;
    }
}
