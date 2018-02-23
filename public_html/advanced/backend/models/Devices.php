<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "devices".
 *
 * @property int $device_id
 * @property string $csid
 * @property string $device_udid
 * @property string $device_type
 * @property string $created_at
 * @property string $updated_at
 */
class Devices extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'devices';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['csid'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['csid', 'jbid', 'device_udid', 'device_token'], 'string'],
            [['device_type'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'device_id' => 'Device ID',
            'csid' => 'Csid',
            'jbid'=> 'JBID',
            'device_udid' => 'Device Udid',
            'device_type' => 'Device Type',
            'device_token' => 'Device Token',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
