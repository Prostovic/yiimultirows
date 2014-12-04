yiimultirows
============

Yii extension for dinamically adding models: widget to show form, behavior with methods to create, validate, delete models.

Some descriptions:

1. I will talk about two types of records "Main" and "Slave".
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
3. Edit form with "Main" record data will have any number of "Slave" records data. We can add, remove, validate all "Slave" records and validation errors will be near correspond "Salve" record.

## Show form with dinamically adding records

#### Prepare view to display "Slave" records
Create standart views for "Slave" table using gii or command line.

Change standart prepared "Slave" view _form.php to use models array and change it's name.

**Standart "Slave" _form.php:**
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

**New changed "Slave" view _editrow.php** ( delete ActiveForm widget, summary block, submit button and added link to delete record and $index parameter ):

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

#### Prepare view to display "Main" record

Create standart views for "Main" table using gii or command line.

Change standart prepared "Main" view _form.php to use "Slave" models array (add widget to add, remove, change slave records):

```
<div class="form">

<?php

$form=$this->beginWidget('CActiveForm', array(
	'id'=>'main-form',
	'enableAjaxValidation'=>true,
	'clientOptions' => array(
		'validateOnType' => false,
		'validateOnChange' => false,
		'validateOnSubmit' => true,
	),
));

?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


		<div class="row">
			<?php echo $form->labelEx($model,'name'); ?>
			<?php echo $form->textField($model,'name',array('size'=>32,'maxlength'=>32)); ?>
			<?php echo $form->error($model,'name'); ?>
		</div>

		<div class="row">
			<?php echo $form->labelEx($model,'value'); ?>
			<?php echo $form->textField($model,'value',array('size'=>32,'maxlength'=>32)); ?>
			<?php echo $form->error($model,'value'); ?>
		</div>

		<div class="row buttons">
			<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
		</div>

/*
 * Add widget to view slave records
 */

		<h1>Slave records <a href="" id="addslaverecord">Add new Slave</a></h1>
<?php
$this->Widget(
	'ext.multirows.MultirowsWidget',          // Slave widget path
	array(
		'model'   => 'Slave',                   // slave model name
		'records' => $model->slave,             // relation to slave records
		'form' => $form,                        // form obgect from this view
		'rowview' => '//slave/_editrow',        // slave record edit view
		'addlinkselector' => '#addslaverecord', // jQuery selector used to create new Slave record
		'dellinkselector' => '.delslaverecord', // jQuery selector in slave record edit view used to remove slave record
		'formselector' => '#main-form',         // jQuery selector Main record form
	)
);
?>


<?php $this->endWidget(); ?>

</div><!-- form -->
```

#### Copy extension to your project

Copy this extension to protected/extensions/multirows directory.

##### Now you can see "Main" record form with ability to add, remove, change "Slave" records.

## Prepare "Main" controller to validate, create, delete records

#### Create and update "Main" record
I will simplify actions and use only one (actionUpdate):

```
	public function actionCreate()
	{
		$this->actionUpdate(); // this is to simplify code
/*
 *************************************************************
 *                                                           *
 * Next in this comment block is standart CRUD creation code *
 * Wi will not use it                                        *
 *                                                           *
 *************************************************************
		$model=new Main;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Main']))
		{
			$model->attributes=$_POST['Main'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('create',array(
			'model'=>$model,
		));
*/
	}

	/**
	 *
	 *
	 *
	 */
	public function actionUpdate($id=0)
	{
		/*
		 ******************************************************
		 *   This part is to simplify create and update code  *
		 ******************************************************
		 */
		if( $id == 0 ) {
			$model = new Main;
		}
		else {
			$model = $this->loadModel($id);
		}

		/*
		 ******************************************************
		 *   This part is to use function with Slave records  *
		 ******************************************************
		 */
		$this->attachBehavior('MultirowsBehavior', array(
			'class' => 'ext.multirows.MultirowsBehavior',
		));
		
		/*
		 ******************************************************
		 *   Ajax validation Main and Slave records           *
		 ******************************************************
		 */
		$this->ajaxValidateMultirow(
			array(
				array('model' => $model,  ), // Main record
				array('model' => 'Slave', ), // Slave record model name, we can use any slave models
				// array('model' => 'Addition', ), // another Slave model
			),
			'main-form' // id Main edit form
		);

		if( isset($_POST['Main']) ) {

		/*
		 *************************************************************
		 *   Save Main and Slave records (replacment standart code)  *
		 *************************************************************
		 */
			$errors = $this->saveMultirow(
				$model, // Main model
				array( // Array with slave models, we can use string with model name ore array with key 'model' and it's value of model name
					'Slave', // Slave models
					// array('model' => 'Addition', ), // another Slave models
				)
			);

			if( count($errors) == 0 ) {
				$this->redirect(array('admin'));
			}
		/*
		 ******************************************************
		 *   Next block is standart CRUD creation text        *
		 ******************************************************
			$model->attributes=$_POST['Main'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
*/
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}
```

#### Delete "Main" record

```
	public function actionDelete($id)
	{
		/*
		 ******************************************************
		 *   Next block is standart CRUD creation text        *
		 ******************************************************
		$this->loadModel($id)->delete();
*/
		/*
		 ******************************************************
		 *   This part is to use function with Slave records  *
		 ******************************************************
		 */
		$model = $this->loadModel($id);
		$this->attachBehavior('MultirowsBehavior', array(
			'class' => 'ext.multirows.MultirowsBehavior',
		));

		/*
		 *************************************************************
		 *  Remove Main and Slave records                            *
		 *************************************************************
		 */
		$this->deleteMultirow(
			$model,  // Main model
			array(   // Array with slave models, we can use string with model name ore array with key 'model' and it's value of model name
				'Slave', // Slave models
				// array('model' => 'Addition', ), // another Slave models
			)
		);

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}
```

##### Now you can edit, validate and delete "Main" record with all "Slave" records.
