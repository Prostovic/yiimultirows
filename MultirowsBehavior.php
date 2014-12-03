<?php
/**
 * MultirowsBehavior class file.
 *
 * @author Victor Kozmin <promcalc@gmail.com>
 * @license http://directory.fsf.org/wiki/License:BSD_3Clause
 */

/**
 * MultirowsBehavior provides a set of methods that can help to simplify the work
 * with dinamically added models.
 *
 * The 'validateMultirow' will validate models and return errors array or json encoded
 * errors array.
 *
 * The 'ajaxValidateMultirow' will perform AJAX form validation for form with dinamically
 * added models.
 *
 * The 'saveMultirow' will save master and relational models
 *
 * The 'deleteMultirow' will delete master and relational models
 *
 * The following is a piece of sample view code showing how to use MultirowsBehavior:
 *
 * <pre>
 * // part cotrollers method update or create:
 *
 * 	public function actionUpdate($id=0)
 * 	{
 * 		if( $id == 0 ) {
 * 			$model = new Main;
 * 		}
 * 		else {
 * 			$model = $this->loadModel($id);
 * 		}
 *
 * 		$this->attachBehavior('MultirowsBehavior', array(
 * 			'class' => 'ext.multiroute.MultirowsBehavior',
 * 		));
 * 		
 * 		$this->ajaxValidateMultirow(
 * 			array(
 * 				array('model' => $model,  ),
 * 				array('model' => 'Slave', ),
 * 			),
 * 			'main-form'
 * 		);
 * 
 * 		if( isset($_POST['Main']) ) {
 * 
 * 			$errors = $this->saveMultirow(
 * 				$model,
 * 				array(
 * 					array('model' => 'Slave'),
 * 					array('model' => 'Addition', ),
 * 				)
 * 			);
 * 			if( count($errors) == 0 ) {
 * 				$this->redirect(array('admin'));
 * 			}
 * 		}
 * 
 * 		$this->render('update',array(
 * 			'model'=>$model,
 * 		));
 * 	}
 * 
 * 	public function actionDelete($id)
 * 	{
 * 		$model = $this->loadModel($id);
 * 		$this->attachBehavior('MultirowsBehavior', array(
 * 			'class' => 'ext.multiroute.MultirowsBehavior',
 * 		));
 * 
 * 		$this->deleteMultirow(
 * 			$model,
 * 			array(
 * 				'Slave',
 * 				array('model' => 'Addition', ),
 * 			)
 * 		);
 * 
 * 		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
 * 		if(!isset($_GET['ajax']))
 * 			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
 * 	}
 * 
 * </pre>
 *
 *
 */ 

class MultirowsBehavior extends CBehavior {

	/**
	 * Perform models validation and return errors array or json emcoded errors array.
	 * @param array $aParam the array of models to valadate. Each element of array 
	 * can be ActiveRecord object, or class name, or array with keys 'model', 'fields'.
	 * Element 'model' can be ActiveRecord object, or class name. Element 'fields' 
	 * can be ommited ( will be test all object attributes ) or can be array with attribute names.
	 * @param bool $jsonRes is need to json encode validation result
	 * @return array or string the error messages, array or json encoded string.
	 */ 
	public function validateMultirow($aParam, $jsonRes = true) {
		$errors = array();
		foreach($aParam As $modelaData) {
			$attributes = null;
			if( is_string($modelaData) ) {
				$sClass = $modelaData;
				$model = new $sClass;
			}
			else if( is_object($modelaData) && is_subclass_of($modelaData, 'CModel') ) {
				$sClass = get_class($modelaData);
				$model = $modelaData;
			}
			else if( is_array($modelaData) ) {
				if( !isset($modelaData['model']) ) {
					continue;
				}

				if( isset($modelaData['fields']) ) {
					$attributes = $modelaData['fields'];
				}

				if( is_string($modelaData['model']) ) {
					$sClass = $modelaData['model'];
					$model = new $sClass;
				}
				else if( is_object($modelaData['model']) && is_subclass_of($modelaData['model'], 'CModel') ) {
					$sClass = get_class($modelaData['model']);
					$model = $modelaData['model'];
				}
				else {
					// тут нужно выдать ошибку
					$this->printError('validateMultirow() not found model', $modelaData);
//					Yii::log('MultirowsBehavior::validateMultirow() error : not found model for ' . print_r($modelaData, true), 'profile');
					continue;
				}
			}
			else {
				// тут нужно выдать ошибку
				$this->printError('validateMultirow() not found model', $modelaData);
//				Yii::log('MultirowsBehavior::validateMultirow() error : not found model for ' . print_r($modelaData, true), 'profile');
				continue;
			}

			if( !isset($_POST[$sClass]) ) {
				continue;
			}

			$oPost = $_POST[$sClass];

			if( isset($oPost[0]) ) {
				// если массив, идем по всем элементам массива
				foreach($oPost As $k=>$v) {
					if( $k == 0 ) {
						// пропускаем нулевой скрытый элемент
						continue;
					}
					$model->attributes = $v;
					$model->validate($attributes);
					if( $model->hasErrors() ) {
						foreach( $model->getErrors() As $att => $err) {
							$sIdFld = CHtml::activeId($model, '['.$k.']'.$att);
							$errors[ $sIdFld ] = $err;
						}
					}
				}
			}
			else {
				// если не массив - одиночная модель
				$model->attributes = $oPost;
				$model->validate($attributes);
				if( $model->hasErrors() ) {
					foreach( $model->getErrors() As $att => $err) {
						$sIdFld = CHtml::activeId($model, $att);
						$errors[ $sIdFld ] = $err;
					}
				}
			}
		}
		return $jsonRes ? (function_exists('json_encode') ? json_encode($errors) : CJSON::encode($errors)) : $errors;
	}

	/**
	 * Perform form ajax validation.
	 * @param array $aParam the array of models to valadate. Each element of array 
	 * can be ActiveRecord object, or class name, or array with keys 'model', 'fields'.
	 * Element 'model' can be ActiveRecord object, or class name. Element 'fields' 
	 * can be ommited ( will be test all object attributes ) or can be array with attribute names.
	 * @param string $formId form id to ajax test
	 * @param bool $bFinishApp is need to finish app after validation
	 * @return string the error json encoded string.
	 */ 
	public function ajaxValidateMultirow($aParam, $formId, $bFinishApp = true) {
		$sErr = '';
		if( isset($_POST['ajax']) && $_POST['ajax']=== $formId ) {
			$sErr = $this->validateMultirow($aParam, true);
			if( $bFinishApp ) {
				echo $sErr;
				Yii::app()->end();
				return '';
			}
		}

		return $sErr;
	}

	/**
	 * Save model with it relational models
	 * @param CActiveRecord $model the model to save
	 * @param array $aSlave array with relational madels names. Each arrays element can be string (class name) or 
	 *   array with key 'model' which value is class name.
	 * @return array the error data.
	 */ 
	public function saveMultirow($model, $aSlave=array()) {
		$aErrors = array();

		$sClass = get_class($model);
		$oConnection = $model->getDbConnection();
		$model->attributes = $_POST[$sClass];

		$transaction = $oConnection->beginTransaction();

		if( !$model->save() ) {
			$aErrors[get_class($model)] = $model->getErrors();
			$transaction->rollback();
			$this->printError('saveMultirow() error save ' . get_class($model), $model->getErrors());
//			Yii::log('MultirowsBehavior::saveMultirow() error : save ' . get_class($model) . ' : ' . print_r($model->getErrors(), true), 'profile');
			return $aErrors;
		}
		try {

			foreach($aSlave as $aData) {
				// проходим по всем полученным моделям
				if( is_string($aData) ) {
					list($sRelName, $aRelData) = $this->getRelation($model, $aData);
					$sClass = $aData;
				}
				else if( is_array($aData) && isset($aData['model']) ) {
					list($sRelName, $aRelData) = $this->getRelation($model, $aData['model']);
					$sClass = $aData['model'];
				}
				else {
					// тут нужно выдать ошибку
					$this->printError('saveMultirow() error : not found model', $aData);
//					Yii::log('MultirowsBehavior::saveMultirow() error : not found model for ' . print_r($aData, true), 'profile');
					continue;
				}

				if( $aRelData === null ) {
					// тут нужно выдать ошибку, потому что не нашли в связях главной модели
					$this->printError('saveMultirow() error : not found ' . $sClass . ' in relations');
//					Yii::log('MultirowsBehavior::saveMultirow() error : not found ' . $sClass . ' in relations', 'profile');
					continue;
				}

				$aExists = $model->$sRelName;

				foreach($_POST[$sClass] as $i=>$aPostData) {
					if( $i == 0 ) {
						continue;
					}

					// берем существующие записи и их меняем
					$a = each($aExists);
					if( $a === false ) {
						// если существующие кончились, то создаем нувую запись
						$ob = new $sClass;
					}
					else {
						$ob = $a['value'];
					}

					$ob->attributes = $aPostData;
					// из данных о связях устанавливаем поле связи по первичному ключу главной записи
					$ob->{$aRelData[2]} = $model->primaryKey;
					if( !$ob->save() ) {
						$aErrors[$sClass] = $model->getErrors();
						$this->printError(
							'saveMultirow() error save ' . $sClass,
							array(
								'attributes' => $ob->attributes,
								'errors' => $ob->getErrors(),
							)
						);
//						Yii::log('MultirowsBehavior::saveMultirow() error : save ' . $sClass . ' [' . implode(', ', $ob->attributes) . '] ' . print_r($ob->getErrors(), true), 'profile');
					}
				}

				while( ($a = each($aExists)) !== false ) {
					// удаляем лишние записи
					$a['value']->delete();
				}
			}

			if( count($aErrors) > 0 ) {
				$transaction->rollback();
			}
			else {
				$transaction->commit();
			}
		}
		catch(Exception $e) {
//			Yii::log('MultirowsBehavior::saveMultirow() error : save: ' . $e->getMessage(), 'profile');
			$this->printError('saveMultirow() save error ' . $e->getMessage());
			$aErrors[$sClass] = $e->getMessage();
			$transaction->rollback();
		}

		return $aErrors;
	}
 
	/**
	 * Delete model with it relational models
	 * @param CActiveRecord $model the model to save
	 * @param array $aSlave array with relational madels names. Each arrays element can be string (class name) or 
	 *   array with key 'model' which value is class name.
	 */ 
	public function deleteMultirow($model, $aSlave=array()) {
		$oConnection = $model->getDbConnection();
		$transaction = $oConnection->beginTransaction();
		try {
			foreach($aSlave as $aData) {
				// проходим по всем полученным моделям
				if( is_string($aData) ) {
					list($sRelName, $aRelData) = $this->getRelation($model, $aData);
					$sClass = $aData;
				}
				else if( is_array($aData) && isset($aData['model']) ) {
					list($sRelName, $aRelData) = $this->getRelation($model, $aData['model']);
					$sClass = $aData['model'];
				}
				else {
					// тут нужно выдать ошибку
					$this->printError('deleteMultirow() error : not found model', $aData);
//					Yii::log('MultirowsBehavior::deleteMultirow() error : not found model for ' . print_r($aData, true), 'profile');
					continue;
				}

				if( $aRelData === null ) {
					// тут нужно выдать ошибку, потому что не нашли в связях главной модели
//					Yii::log('MultirowsBehavior::deleteMultirow() error : not found ' . $sClass . ' in relations', 'profile');
					$this->printError('deleteMultirow() error : not found ' . $sClass . ' in relations');
					continue;
				}

				$aExists = $model->$sRelName;
				foreach($aExists As $ob) {
					$ob->delete();
				}
			}
			$model->delete();
			$transaction->commit();
		}
		catch(Exception $e) {
//			Yii::log('MultirowsBehavior::deleteMultirow() error : save: ' . $e->getMessage(), 'profile');
			$this->printError('deleteMultirow() save error ' . $e->getMessage());
			$transaction->rollback();
		}

	}

	/**
	 * Save model with its relational models
	 * @param CActiveRecord $model the model to find relation
	 * @param string $slaveTable table name to find HAS_MANY relation
	 * @return array the relation data, index 0 - relation name, index 1 - array with relation parameters.
	 */ 
	public function getRelation($model, $slaveTable) {
		$aRel = $model->relations();
		$aRelData = null;
		$sRelName = null;
		foreach($aRel As $k=>$v) {
			// ищем в связях главной модели
			if( ($v[1] == $slaveTable) && ($v[0] == CActiveRecord::HAS_MANY) ) {
				$aRelData = $v;
				$sRelName = $k;
				break;
			}
		}
		return array($sRelName, $aRelData);
	}

	/**
	 * Save model with its relational models
	 * @param string $sMessage message text
	 * @param array $data additional data
	 * @param bool $isNeedException need to rise Exception
	 */ 
	public function printError($sMessage, $data = null, $isNeedException = false) {
			Yii::log('MultirowsBehavior: ' . $sMessage . ( $data=== null ? '' : (' data = ' . print_r($data, true)) ), 'error');
			if( $isNeedException ) {
				//
			}
	}

}
