<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Devices */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="devices-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'csid')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'device_udid')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'device_type')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
