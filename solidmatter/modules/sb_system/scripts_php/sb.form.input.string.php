<?php

//------------------------------------------------------------------------------
/**
*	@package solidMatter[sbSystem]
*	@subpackage sbForm
*	@author	()((() [Oliver Müller]
*	@version 1.00.00
*/
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
/**
*/
class sbInput_string extends sbInput {
	
	protected $sType = 'string';
	
	protected $aConfig = array(
		'size' => '30',
		'minlength' => '0',
		'maxlength' => '40',
		'required' => 'FALSE',
		'trim' => 'TRUE',
		'regex' => ''
	);
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function checkInput() {
		
		if (mb_strlen($this->mValue) < $this->aConfig['minlength']) {
			$this->sErrorLabel = '$locale/sbSystem/formerrors/string_too_short';
		}
		if (mb_strlen($this->mValue) > $this->aConfig['maxlength']) {
			$this->sErrorLabel = '$locale/sbSystem/formerrors/string_too_long';
		}
		if (mb_strlen($this->mValue) == 0 && $this->aConfig['required'] == 'TRUE') {
			$this->sErrorLabel = '$locale/sbSystem/formerrors/not_null';
		}
		
		$this->additionalChecks();
		
		if ($this->sErrorLabel == '') {
			return (TRUE);
		} else {
			return (FALSE);
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function additionalChecks() { }
	
	
}




?>