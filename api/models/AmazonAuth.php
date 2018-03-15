<?php
namespace api\models;

use Yii;
use yii\base\Model;

class AmazonAuth extends Model
{
    public $AccessToken;

    private $_user;
    private $_authData;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['AccessToken'], 'required'],
        ];
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser());
        }
        
        return false;
    }

    /**
     * Finds user by [[AccessToken]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === null) {

            $authResult = $this->getAuthData();
            
            $data = $authResult->get('UserAttributes');
            $attributes = array();
            foreach ($data as $item) {
                $attributes[$item['Name']] = $item['Value'];
            }
            
            $this->_user = User::findByPhoneAuth($attributes['phone_number']);
        }

        return $this->_user;
    }
    
    public function getAuthData()
    {
        if ($this->_authData === null) {
            $awssdk = Yii::$app->awssdk->getAwsSdk();
            $client = $awssdk->createClient('CognitoIdentityProvider');
            $this->_authData = $client->getUser([
                'AccessToken' => $this->AccessToken
            ]);
        }
        
        return $this->_authData;
    } // end getAuthData
}
