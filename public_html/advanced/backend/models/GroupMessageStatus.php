<?php

namespace backend\models;

use Yii;

class GroupMessageStatus extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'group_message_status';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['msgId','readId','deliveredId'], 'string'],
            [['datetime'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'msgId' => 'Message ID',
            'readId' => 'Read ID',
            'deliveredId' => 'Delivered ID',
            'datetime' => 'datetime'
        ];
    }
}
