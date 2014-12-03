<?php

/**
 * MultirowsWidget class file.
 *
 * @author Victor Kozmin <promcalc@gmail.com>
 * @license http://directory.fsf.org/wiki/License:BSD_3Clause
 */

/**
 * MultirowsBehavior provides a set of methods that can help to simplify the work
 * with dinamically added models.
 *
 * The following is a piece of sample view code showing how to use MultirowsWidget:
 *
 * <pre>
 * <?php
 * // part _form.php file of main record
 * $form=$this->beginWidget('CActiveForm', array(
 * 	'id'=>'main-form',
 * 	'enableAjaxValidation'=>true,
 * 	'clientOptions' => array(
 * 		'validateOnType' => false,
 * 		'validateOnChange' => false,
 * 		'validateOnSubmit' => true,
 * 	),
 * ));
 * 
 * ?>
 * 
 * 	<p class="note">Fields with <span class="required">*</span> are required.</p>
 * 
 * 	<?php echo $form->errorSummary($model); ?>
 * 
 * 
 * 	<div class="screenpart">
 * 		<div class="row">
 * 			<?php echo $form->labelEx($model,'name'); ?>
 * 			<?php echo $form->textField($model,'name',array('size'=>32,'maxlength'=>32)); ?>
 * 			<?php echo $form->error($model,'name'); ?>
 * 		</div>
 * 
 * 		<div class="row">
 * 			<?php echo $form->labelEx($model,'value'); ?>
 * 			<?php echo $form->textField($model,'value',array('size'=>32,'maxlength'=>32)); ?>
 * 			<?php echo $form->error($model,'value'); ?>
 * 		</div>
 * 
 * 		<div class="row buttons">
 * 			<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
 * 		</div>
 * 	</div>
 * 
 * 	<div class="screenpart">
 * 		<h1>Slave records <a href="" id="addslaverecord">Add new Slave</a></h1>
 * 
 * <?php
 * $this->Widget(
 * 	'ext.multiroute.MultirowsWidget',
 * 	array(
 * 		'model'   => 'Slave',
 * 		'records' => $model->slave,
 * 		'form' => $form,
 * 		'rowview' => '//slave/_editrow',
 * 		'addlinkselector' => '#addslaverecord',
 * 		'dellinkselector' => '.delslaverecord',
 * 		'formselector' => '#main-form',
 * 	)
 * );
 * ?>
 * 
 * 		<div class="clearfix"></div>
 * 	</div>
 * <?php $this->endWidget(); ?>
 * 
 * </div><!-- form --> * 
 *
 * // part slave/_editrow file of slave record
 * <div class="slaverow">
 * 		<div class="row">
 * 			<?php echo $form->labelEx($model,'['.$index.']param'); ?>
 * 			<?php echo $form->textField($model,'['.$index.']param',array('size'=>32,'maxlength'=>32)); ?>
 * 			<?php echo $form->error($model,'['.$index.']param'); ?>
 * 		</div>
 * 
 * 		<div class="row">
 * 			<?php echo $form->labelEx($model,'['.$index.']selected'); ?>
 * 			<?php echo $form->checkBox($model,'['.$index.']selected'); ?>
 * 			<?php echo $form->error($model,'['.$index.']selected'); ?>
 * 		</div>
 * 
 * 		<div class="row">
 * 			<?php echo $form->labelEx($model,'['.$index.']type'); ?>
 * 			<?php echo $form->dropDownList($model,'['.$index.']type', Slave::$aTypes); ?>
 * 			<?php echo $form->error($model,'['.$index.']type'); ?>
 * 		</div>
 * 
 * 		<div class="row">
 * 			<?php echo $form->labelEx($model,'['.$index.']created'); ?>
 * 			<?php echo $form->textField($model,'['.$index.']created'); ?>
 * 			<?php echo $form->error($model,'['.$index.']created'); ?>
 * 		</div>
 * 
 * 		<div class="row">
 * 			<a href="" class="delslaverecord">Delete Slave</a>
 * 		</div>
 * 	</div>
 * 
 * 
 * </pre>
 *
 *
 */ 

class MultirowsWidget extends CWidget {
	/**
	 * @var string model bame to genereate form elements
	 */
	public $model = '';

	/**
	 * @var array existing ActiveRecords of $model
	 */
	public $records = array();

	/**
	 * @var CActiveForm form object for render fields
	 */
	public $form = null;

	/**
	 * @var array default attributes for new created objects
	 */
	public $defaultattributes = array();

	/**
	 * @var string view path to render form fields
	 */
	public $rowview = '';

	/**
	 * @var string jQuery selector to find link which add new model fields
	 */
	public $addlinkselector = '';

	/**
	 * @var string jQuery selector to find link which delete model fields.
	 */
	public $dellinkselector = '';

	/**
	 * @var string jQuery selector to find form object.
	 */
	public $formselector = '';
	
	public function init() {}

	public function run() {
		$controller = $this->controller;
		$sRowClass = 'row' . $this->model . substr(md5(microtime()), mt_rand(0, 10), mt_rand(3, 6)); 
		$ob = new $this->model;
		if( ! empty($this->defaultattributes) ) {
			$ob->attributes = $this->defaultattributes;
		}
		$aData = array_merge(array($ob), $this->records);
		foreach($aData As $k=>$v) {
			echo '<div class="' . $sRowClass . '">';
			$this->controller->renderPartial(
				$this->rowview, 
				array(
					'index' => $k,
					'model' => $v,
					'form' => $this->form,
				)
			);
			echo '</div>';
		}

		$sAssetDir = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
		$sJs = $sAssetDir . 'js' . DIRECTORY_SEPARATOR . 'multirows.js';
		Yii::app()->clientScript->registerScriptFile(Yii::app()->assetManager->publish($sJs));
		$sJs = <<<EOT
jQuery(function($) {
	Multirow({
		rowclass: ".{$sRowClass}",
		model: "{$this->model}",
		addlinkselector: "{$this->addlinkselector}",
		dellinkselector: "{$this->dellinkselector}",
		formselector: "{$this->formselector}"
	});
});
EOT;
		Yii::app()->clientScript->registerScript($this->model . "multirow", $sJs, CClientScript::POS_END);
	}
}