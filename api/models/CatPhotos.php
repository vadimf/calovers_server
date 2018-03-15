<?php 
namespace api\models;

use yii\db\ActiveRecord;

class CatPhotos extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%cat_photos}}';
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
            [['photo', 'thumbnail'], 'url']
        ];
    }
    
    public function getCat()
    {
        return $this->hasOne(Cat::className(), ['id' => 'cat_id']);
    } // end getCat
    
}