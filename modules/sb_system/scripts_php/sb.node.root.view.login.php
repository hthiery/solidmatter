<?php

//------------------------------------------------------------------------------
/**
* @package	solidMatter[sbSystem]
* @author	()((() [Oliver Müller]
* @version	1.00.00
*/
//------------------------------------------------------------------------------

import('sb.tools.datetime');

//------------------------------------------------------------------------------
/**
*/
class sbView_root_login extends sbView {
	
	protected $bLoginRequired = FALSE;
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function execute($sAction) {
		
		global $_RESPONSE;
		
		switch ($sAction) {
			
			// just display the login screen
			case 'display':
				
				$formLogin = $this->buildForm();
				$formLogin->saveDOM();
				
				$_RESPONSE->addData($formLogin);
				
				return ($this->nodeSubject);
				
			// attempt to login
			case 'login':
				
				$formLogin = $this->buildForm();
				$formLogin->recieveInputs();
				$aInputs = $formLogin->getValues();
				
				if ($formLogin->checkInputs()) { // form is filled correctly, attempt login
					
					$aResult = User::attemptLogin($aInputs['login'], $aInputs['password']);
					
					if ($aResult['login_successful']) {
						
						// store user info
						$aData['user_id']			= $aResult['user_uuid'];
						$aData['user_ip']			= $_REQUEST->getServerValue('REMOTE_ADDR');
						$aData['secure_login']		= TRUE;
						$aData['is_admin']			= FALSE;
						$aData['fingerprint']		= md5($_REQUEST->getServerValue('REMOTE_ADDR').$_REQUEST->getServerValue('HTTP_USER_AGENT'));
						
						// store group info
						$nodeUser = $this->crSession->getNodeByIdentifier($aResult['user_uuid']);
						$niParents = $nodeUser->getParents();
						$aParentUUIDs = array();
						foreach ($niParents as $nodeParent) {
							//echo $nodeParent->getProperty('uid');
							$aParentUUIDs[] = $nodeParent->getProperty('jcr:uuid');
							if ($nodeParent->getProperty('uid') == 'sb_system:admins') {
								$aData['is_admin'] = TRUE;
							}
						}
						$aData['groups'] = $aParentUUIDs;
						
						sbSession::addData('userdata', $aData);
						
						// TODO: implement durable sessions
						/*if (isset($aInputs['stayloggedin'])
							&& $aInputs['stayloggedin'] == 'TRUE'
							&& get_config('system', 'SECURITY_STAY_LOGGEDIN_ALLOWED') == 'TRUE'
							) {
							if (is_permitted('SYSTEM_STAY_LOGGEDIN')) {
								setcookie('solidBrickz', $rsLogin->Column('id').':'.md5($rsLogin->Column('s_password')), time()+60*60*24*100);
							} else {
								$this->ThrowError('ERROR_STAY_LOGGEDIN_NOT_ALLOWED');
								return (FALSE);
							}
						}*/
						
						$_RESPONSE->redirect('-');
						
						// log successful login
						if (Registry::getValue('sb.system.privacy.login')) {
							$sUserText = $aInputs['login'] = 'anonymous';
						} else {
							$sUserText = '"'.$aInputs['login'].'"';
						}
						$this->logEvent(System::INFO, 'LOGIN_SUCCESSFUL', $sUserText.' with fingerprint "'.$aData['fingerprint'].'" from "'.$aData['user_ip'].'"');
						
					} else {
						
						// add info to form object
						switch ($aResult['failure_reason']) {
							case 'inexistent_user':
							case 'wrong_password':
								$formLogin->setFormError('$locale//formerrors/wrong_logindata');
								break;
							case 'locked_manually':
							case 'locked_temporarily':
								$formLogin->setFormError('$locale//formerrors/account_locked');
								break;
							case 'account_inactive':
								$formLogin->setFormError('$locale//formerrors/account_inactive');
								break;
							default:
								throw new sbException('login failed for unknown reason "'.$aResult['failure_reason'].'"');
								break;
						}
						
						// log security event
						$this->logEvent(System::SECURITY, 'LOGIN_FAILED', '"'.$aInputs['login'].'" => "'.$aResult['failure_reason'].'"');
						
					}
					
				} else { // form isn't even filled like it should be
					
					// log security event if captcha was incorrect
					if (Registry::getValue('sb.system.security.login.captcha.enabled')) {
						if ($formLogin->hasError('captcha')) {
							$ifCaptcha = $formLogin->getInput('captcha');
							$this->logEvent(System::SECURITY, 'CAPTCHA_INCORRECT', '"'.$aInputs['captcha'].'" given, required "'.$ifCaptcha->getSequence().'"');
						}
					}
					
				}
				
				$formLogin->saveDOM();
				$_RESPONSE->addData($formLogin);
				return ($this->nodeSubject);
				//break;
			
			// logout and redirect to login screen
			case 'logout':
				sbSession::destroy();
				$_RESPONSE->redirect('-');
				break;
			
			// used to access all generated captchas
			case 'getCaptcha':
				import('sb.image.captcha');
				if ($_REQUEST->getParam('uid') == 'login_backend') {
					$sType = Registry::getValue('sb.system.security.login.captcha.type');
					$imgChallenge = new CaptchaImage(200, 80, 4);
					$imgChallenge->generate();
					sbSession::$aData['captcha']['login_backend'] = $imgChallenge->getSequence();
					$imgChallenge->output(GIF);
				}
				break;
			
			default:
				throw new sbException(__CLASS__.': action not recognized ('.$sAction.')');
			
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	private function buildForm() {
		
		$formLogin = new sbDOMForm(
			'login_backend',
			'$locale/system/general/labels/login',
			'/-/login/login',
			$this->crSession
		);
		$formLogin->addInput('login;string;required=TRUE', '$locale/system/general/labels/login');
		$formLogin->addInput('password;password;required=TRUE', '$locale/system/general/labels/password');
		if (Registry::getValue('sb.system.security.login.captcha.enabled')) {
			$formLogin->addInput('captcha;captcha;required=TRUE;uid=login_backend', '$locale/system/general/labels/captcha');
		}
		
		$formLogin->addSubmit('$locale/system/general/actions/login');
		
		return ($formLogin);
		
	}
		
}

?>