<?php 
namespace api\models;

use Yii;
use yii\db\ActiveRecord;

class UserAuthorization extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%user_authorizations}}';
    }
    
    public function fields()
    {
        return [
            'user_id',
            'token'
        ];
    }
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    } // end getCat

    /**
     * Generates authentication key
     */
    public function generateToken()
    {
        $this->token = Yii::$app->security->generateRandomString();
    } // end generateToken
    
}