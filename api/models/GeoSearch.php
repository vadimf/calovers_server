<?php
namespace api\models;

use yii\base\Model;

/**
 * GeoSearch
 */
class GeoSearch extends Model
{
    public $lat;
    public $lng;
    public $distance;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lat', 'lng'], 'required'],
            ['distance', 'default', 'value' => '20'],
        ];
    }
}
