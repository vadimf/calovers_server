<?php
namespace api\models;

use yii\base\Model;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class Invite extends Model
{
    public $phone;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['phone'], 'required'],
            // normalize "phone" input
            ['phone', 'filter', 'filter' => function ($value) {
                // normalize phone input here
                $phoneUtil = PhoneNumberUtil::getInstance();
                $proto = $phoneUtil->parse($value);
                
                return $phoneUtil->format($proto, PhoneNumberFormat::E164);
            }],
        ];
    }

}
