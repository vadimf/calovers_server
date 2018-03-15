<?php 
namespace api\models;

use yii\db\ActiveRecord;

class FeedstationPermission extends ActiveRecord
{
    const STATUS_REQUESTED = 'requested';
    const STATUS_INVITED = 'invited';
    const STATUS_JOINED = 'joined';
    const STATUS_DELETED = 'deleted';
    
    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';
    
    public static function tableName()
    {
        return '{{%feedstation_users}}';
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
    
    public function getFeedstation()
    {
        return $this->hasOne(Feedstation::className(), ['id' => 'feedstation_id']);
    } // end getFeedstation
    
}