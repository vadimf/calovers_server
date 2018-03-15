<?php 
namespace api\models;

use Yii;
use yii\db\ActiveRecord;

class UserFeedback extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%user_feedback}}';
    }
    
    public function rules()
    {
        return [
            [['subject', 'description'], 'required'],
        ];
    }
    
//     public function beforeSave($insert)
//     {
//         $result = parent::beforeSave($insert);
    
//         if ($result) {
//             if ($insert) {
//                 $this->created = Yii::$app->user->identity->id;
//             }
//         }
    
//         return $result;
//     } // end beforeSave
    
}