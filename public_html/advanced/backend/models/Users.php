<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $user_id
 * @property string $csid
 * @property string $jbid
 * @property string $password
 * @property string $email
 * @property string $fullname
 * @property string $phone
 * @property string $tags
 * @property string $custom_data
 * @property string $external_id
 * @property string $last_sign_in
 * @property string $created_at
 * @property string $updated_at
 */
class Users extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['csid', 'jbid', 'password'], 'required'],
            [['tags', 'custom_data'], 'string'],
            [['last_sign_in', 'created_at', 'updated_at'], 'safe'],
            [['csid', 'jbid','access_token', 'password', 'email', 'fullname', 'external_id'], 'string', 'max' => 100],
            [['phone'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'csid' => 'Csid',
            'jbid' => 'Jbid',
            'password' => 'Password',
            'access_token'=>'Access Token',
            'email' => 'Email',
            'fullname' => 'Fullname',
            'phone' => 'Phone',
            'tags' => 'Tags',
            'custom_data' => 'Custom Data',
            'external_id' => 'External ID',
            'last_sign_in' => 'Last Sign In',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function generateAccessToken()
    {
        $this->access_token = Yii::$app->security->generateRandomString();
    }
}
