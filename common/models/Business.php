<?php 
namespace common\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

class Business extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_DELETED = 'deleted';
    
    public $distance;
    
    public static function tableName()
    {
        return '{{%businesses}}';
    } // end tableName
    
    public function fields()
    {
        return [
            'id',
            'name',
            'category',
            'address',
            'lat',
            'lng',
            'link',
            'open_hour',
            'description',
            'phone',
            'created_at',
        ];
    } // end fields
    
    public function rules()
    {
        return [
            [['name', 'address', 'lat', 'lng', 'category'], 'required'],
            [['description', 'address', 'link', 'phone', 'open_hour'], 'string'],
            [['lat', 'lng'], 'double'],
            ['category', 'in', 'range' => ['food', 'veterinary']]
        ];
    } // end rules

    public function beforeSave($insert)
    {
        $result = parent::beforeSave($insert);
    
        if ($result) {
            if (!empty($this->lng) && !empty($this->lat)) {
                $this->location = new Expression(
                    'ST_SetSRID(ST_MakePoint('.$this->lng.', '.$this->lat.'), 4326)'
                );
            }
        }
    
        return $result;
    } // end beforeSave
    
    public static function findNearby(\api\models\GeoSearch $model)
    {
        return static::find()
            ->select([
                '*',
                new Expression("(location::geography <-> 'SRID=4326;POINT($model->lng $model->lat)'::geography) / 1000 AS distance"),
            ])
            ->where(
                "(location::geography <-> 'SRID=4326;POINT($model->lng $model->lat)'::geography) / 1000 <= $model->distance"
            )
            ->andWhere(['status' => self::STATUS_ACTIVE])
            ->all();
    } // end findNearby
}