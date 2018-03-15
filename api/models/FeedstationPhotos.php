<?php 
namespace api\models;

use yii\db\ActiveRecord;

class FeedstationPhotos extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%feedstation_photos}}';
    }
    
    public function fields()
    {
        return [
            'id',
            'photo',
            'thumbnail'
        ];
    }
    
    public function rules()
    {
        return [
            [['photo'], 'required'],
            [['photo', 'thumbnail'], 'url'],
        ];
    }
    
    public function getFeedstation()
    {
        return $this->hasOne(Feedstation::className(), ['id' => 'feedstation_id']);
    } // end getFeedstation
    
}