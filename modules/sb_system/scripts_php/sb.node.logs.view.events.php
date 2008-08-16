<?php

//------------------------------------------------------------------------------
/**
* @package	solidMatter[sbSystem]
* @author	()((() [Oliver Müller]
* @version	1.00.00
*/
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
/**
*/
class sbView_logs_events extends sbView {
	
	public function execute($sAction) {
		
		global $_RESPONSE;
		
		switch ($sAction) {
			
			case 'display':
				
				$formFilter = $this->buildFilterForm();
				$formFilter->saveDOM();
				$_RESPONSE->addData($formFilter);
				
				$stmtGetEvents = $this->crSession->prepareKnown('sbSystem/eventLog/getEntries/filtered');
				$stmtGetEvents->bindValue('module', '%', PDO::PARAM_STR);
				$stmtGetEvents->bindValue('type', '%', PDO::PARAM_STR);
				$stmtGetEvents->execute();
				$_RESPONSE->addData($stmtGetEvents->fetchElements('events'));
				
				break;
				
			case 'filter':
				
				$formFilter = $this->buildFilterForm();
				$formFilter->recieveInputs();
				$aInputs = $formFilter->getValues();
				
				$stmtGetEvents = $this->crSession->prepareKnown('sbSystem/eventLog/getEntries/filtered');
				$stmtGetEvents->bindValue('module', '%'.$aInputs['module'].'%', PDO::PARAM_STR);
				$stmtGetEvents->bindValue('type', '%'.$aInputs['type'].'%', PDO::PARAM_STR);
				$stmtGetEvents->execute();
				$_RESPONSE->addData($stmtGetEvents->fetchElements('events'));
				
				$formFilter->saveDOM();
				$_RESPONSE->addData($formFilter);
				
				break;
				
			default:
				throw new sbException(__CLASS__.': action not recognized ('.$sAction.')');
				
		}
	}
	
	private function buildFilterForm() {
		
		$formFilter = new sbDOMForm(
			'filter_events',
			'$locale/system/general/labels/filter',
			//'/backend.view=login&action=login'
			System::getURL($this->nodeSubject, 'events', 'filter'),
			$this->crSession
		);
		
		$formFilter->addInput('type;select;', '$locale/system/general/labels/type');
		$aOptions = array(
			'' => '',
			'SECURITY' => 'SECURITY',
			'ERROR' => 'ERROR',
			'WARNING' => 'WARNING',
			'MAINTENANCE' => 'MAINTENANCE',
			'INFO' => 'INFO',
			'DEBUG' => 'DEBUG'
		);
		$formFilter->setOptions('type', $aOptions);
		$formFilter->addInput('module;select;', '$locale/system/general/labels/module');
		$aOptions = array('' => '');
		foreach (System::getModules() as $sKey => $unused) {
			$aOptions[$sKey] = $sKey;
		}
		$formFilter->setOptions('module', $aOptions);
		$formFilter->addSubmit('$locale/system/general/actions/filter');
		
		return ($formFilter);
		
	}
	
}

?>