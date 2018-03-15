<?php 
namespace api\models;

use yii\db\ActiveRecord;

class CatPermission extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%cat_group_members}}';
    }
    
    public function fields()
    {
        return [
            'user_id',
            'user',
            'role',
            'status'
        ];
    }
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    } // end getUser
    
    public function getCat()
    {
        return $this->hasOne(Cat::className(), ['id' => 'cat_id']);
    } // end getCat
    
}