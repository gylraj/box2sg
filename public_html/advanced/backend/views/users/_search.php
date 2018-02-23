<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UsersSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="users-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'user_id') ?>

    <?= $form->field($model, 'csid') ?>

    <?= $form->field($model, 'jbid') ?>

    <?= $form->field($model, 'password') ?>

    <?= $form->field($model, 'email') ?>

    <?php // echo $form->field($model, 'fullname') ?>

    <?php // echo $form->field($model, 'phone') ?>

    <?php // echo $form->field($model, 'tags') ?>

    <?php // echo $form->field($model, 'custom_data') ?>

    <?php // echo $form->field($model, 'external_id') ?>

    <?php // echo $form->field($model, 'last_sign_in') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
