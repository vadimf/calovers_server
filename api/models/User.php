<?php
namespace api\models;

use Aws\Sns\SnsClient;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_INVITED = 'invited';
    const STATUS_ACCEPTED = 'accepted';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%users}}';
    } // end tableName
    
    public function fields()
    {
        return [
            'name',
            'age',
            'email',
            'phone',
            'address',
            'sex',
            'status',
            'avatar_url',
            'avatar_url_thumbnail',
        ];
    } // end fields
    
    public function getSex()
    {
        return is_null($this->sex) 
            ? 'none' 
            : ($this->sex ? 'male' : 'female');  
    } // end getSex
    
    public static function findByEmailAuth($email)
    {
        return static::find()
            ->join('INNER JOIN', 'user_profiles', 'users.id = user_profiles.user_id')
            ->where([
                'user_profiles.ident' => $email,
                'user_profiles.type'  => 'email'
            ])
            ->one();
    } // end findByEmailAuth
    
    public static function findByPhoneAuth($phone)
    {
        return static::find()
            ->join('INNER JOIN', 'user_profiles', 'users.id = user_profiles.user_id')
            ->where([
                'user_profiles.ident' => $phone,
                'user_profiles.type'  => 'phone'
            ])
            ->one();
    } // end findByPhoneAuth
    
    public function getUserProfile()
    {
        return $this->hasOne(UserProfile::className(), ['user_id' => 'id']);
    } // end getUserProfile
    
    public function getUserAuthorization()
    {
        return $this->hasOne(UserAuthorization::className(), ['user_id' => 'id']);
    } // end getUserAuthorization

    public function getFeedstations($viaFilters = null)
    {
        return $this->hasMany(Feedstation::className(), ['id' => 'feedstation_id'])
            ->viaTable('feedstation_users', ['user_id' => 'id'], function ($query) use ($viaFilters) {
                $query->andWhere(['<>', 'status', 'deleted']);
                if ($viaFilters !== null) {
                    $query->andWhere($viaFilters);
                }
            });
    } // end getFeedstations
    
    public function getCats()
    {
        return $this->hasMany(Cat::className(), ['id' => 'cat_id'])
            ->viaTable('cat_group_members', ['user_id' => 'id']);
    } // end getCats
    
    public function getBusinesses()
    {
        return $this->hasMany(Business::className(), ['user_id' => 'id']);
    } // end getBusinesses
    
    public function getEvents()
    {
        return $this->hasMany(Event::className(), ['user_id' => 'id']);
    } // end getEvents
    
    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->userProfile->token);
    } // end validatePassword
    
    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    } // end setPassword

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }
    
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::find()
            ->join('INNER JOIN', 'user_authorizations', 'user_authorizations.user_id = users.id')
            ->where([
                'user_authorizations.token' => $token
            ])
            ->one();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }
    
    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        $auth = $this->userAuthorization;
        return $auth ? $auth->token : null;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
    
    public static function createUser(Invite $model)
    {
        $user = new User();
        $user->phone = $model->phone;
        $user->status = User::STATUS_INVITED;
        
        if ($user->save() === false) {
            throw new \Exception('Can\'t create user');
        }
        
        $profile = new UserProfile;
        $profile->user_id = $user->getPrimaryKey();
        $profile->type = 'phone';
        $profile->ident = $model->phone;
        
        if ($profile->save() === false) {
            throw new \Exception('Can\'t create user profile');
        }

//         $awssdk = Yii::$app->awssdk->getAwsSdk();
//         $client = $awssdk->createClient('Sns');
        
        $client = SnsClient::factory(array(
            'credentials' => Yii::$app->awssdk->credentials,
            'region'  => 'eu-west-1',
            'version' => Yii::$app->awssdk->version,
        ));
        
        $payload = [
            'PhoneNumber' => $model->phone, // E.164 format
            'Message' => 'You were invited to CatsLovers',
            'MessageAttributes' => [
                'AWS.SNS.SMS.SenderID' => [
                    'DataType' => 'String',
                    'StringValue' => 'CatsLovers',
                ]
            ]
        ];
        
        $result = $client->publish($payload);
        
        return $user;
    } // end createUser
    
}
