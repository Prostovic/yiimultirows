yiimultirows
============

Yii extension for dinamically adding models: widget to show form, behavior with methods to create, validate, delete models.

Some descriptions:

1. I will talk about two types of records "Main" and "Slave"
2. "Main" record has relation to "Slave" like this:
```
	public function relations()
	{
		return array(
//			...
			'slave'=>array(self::HAS_MANY, 'Slave', 'main_id', ),
//			...
		);
	}
```
3. Edit form with "Main" record data will have any number of "Slave" records data. We can add, remove, validate all "Slave" records and validation errors will be near correspond "Salve" record

## Show form with dinamically adding records

#### Prepare view to display "Slave" record.
Create standart views for "Slave" table using gii or command line

Change standart prepared view _form.php to use models array and change it's name

**_form.php:**
```
<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'slave-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'param'); ?>
		<?php echo $form->textField($model,'param',array('size'=>32,'maxlength'=>32)); ?>
		<?php echo $form->error($model,'param'); ?>
	</div>
	...
	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->
```

**New changed file _editrow.php ( delete ActiveForm widget, summary block, submit button and added link to delete record and $index parameter ):**
```
	<div class="slaverow">
		<div class="row">
			<?php echo $form->labelEx($model,'['.$index.']param'); ?>
			<?php echo $form->textField($model,'['.$index.']param',array('size'=>32,'maxlength'=>32)); ?>
			<?php echo $form->error($model,'['.$index.']param'); ?>
		</div>


		<div class="row">
			<a href="" class="delslaverecord">Delete Slave</a>
		</div>
	</div>
```


