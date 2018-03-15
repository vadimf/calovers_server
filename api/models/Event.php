<?php 
namespace api\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

class Event extends ActiveRecord
{
    public $distance;
    
    public static function tableName()
    {
        return '{{%events}}';
    } // end tableName
    
    public function fields()
    {
        return [
            'id',
            'eventType',
            'name',
            'address',
            'lat',
            'lng',
            'description',
            'created',
        ];
    } // end fields
    
    public function rules()
    {
        return [
            [['name', 'lat', 'lng', 'type_id'], 'required'],
            [['description', 'address'], 'string'],
            [['lat', 'lng'], 'double'],
            [['type_id'], 'integer'],
        ];
    } // end rules
    
    public function getCreated()
    {
        return strtotime($this->created_at);
    } // end getCreated

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    } // end getUser
    
    public function getEventType()
    {
        return $this->hasOne(EventType::className(), ['id' => 'type_id']);
    } // end getEventType
    
    public function beforeSave($insert)
    {
        $result = parent::beforeSave($insert);
    
        if ($result) {
            if (!empty($this->lng) && !empty($this->lat)) {
                $this->location = new Expression(
                    'ST_SetSRID(ST_MakePoint('.$this->lng.', '.$this->lat.'), 4326)'
                );
            }
            
            if ($insert) {
                $this->user_id = Yii::$app->user->identity->id;
            }
        }
    
        return $result;
    } // end beforeSave
    
    public static function findNearby(GeoSearch $model)
    {
        return static::find()
            ->select([
                '*',
                new Expression("(location::geography <-> 'SRID=4326;POINT($model->lng $model->lat)'::geography) / 1000 AS distance"),
            ])
            ->where(
                "(location::geography <-> 'SRID=4326;POINT($model->lng $model->lat)'::geography) / 1000 <= $model->distance"
            )
            ->all();
    } // end findNearby

}