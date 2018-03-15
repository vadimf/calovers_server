<?php 
namespace api\models;

use yii\db\ActiveRecord;

class UserProfile extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%user_profiles}}';
    }
    
    public function fields()
    {
        return [
            'user_id',
            'type',
            'ident',
            'token'
        ];
    }
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    } // end getCat
    
}