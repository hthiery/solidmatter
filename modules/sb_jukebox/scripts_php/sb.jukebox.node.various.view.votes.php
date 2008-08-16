<?php

//------------------------------------------------------------------------------
/**
* @package	solidMatter[sbJukebox]
* @author	()((() [Oliver Müller]
* @version	1.00.00
*/
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
/**
*/
class sbView_jukebox_various_votes extends sbJukeboxView {
	
	public function execute($sAction) {
		
		global $_RESPONSE;
		
		switch ($sAction) {
			
			case 'addComment':
				
				// comment form
				$formComment = $this->buildCommentForm();
				$formComment->recieveInputs();
				if ($formComment->checkInputs()) {
					import('sb.tools.strings.conversion');
					$nodeComment = $this->nodeSubject->addNode(str2urlsafe($_REQUEST->getParam('title')), 'sb_system:comment');
					$nodeComment->setProperty('label', $_REQUEST->getParam('title'));
					$nodeComment->setProperty('comment', $_REQUEST->getParam('comment'));
					$this->nodeSubject->save();
					if ($_REQUEST->getParam('silent') == NULL) {
						switch($this->nodeSubject->getPrimaryNodeType()) {
							case 'sb_jukebox:album':
								$_RESPONSE->redirect($this->nodeSubject->getProperty('jcr:uuid'), 'details');
								break;	
							case 'sb_jukebox:artist':
								$_RESPONSE->redirect($this->nodeSubject->getProperty('jcr:uuid'), 'details');
								break;	
							case 'sb_jukebox:track':
								$_RESPONSE->redirect($this->nodeSubject->getProperty('jcr:uuid'), 'details');
								break;
							case 'sb_jukebox:playlist':
								$_RESPONSE->redirect($this->nodeSubject->getProperty('jcr:uuid'), 'details');
								break;
						}
					}
				} else {
					throw new sbException('title or comment missing!');
					$formComment->saveDOM();
					$_RESPONSE->addData($formComment);
				}
				break;
				
			case 'placeVote':
				$iVote = $_REQUEST->getParam('vote');
				$nodeJukebox = $this->nodeSubject->getAncestorOfType('sb_jukebox:jukebox');
				$iMin = Registry::getValue('sb.jukebox.voting.scale.min');
				$iMax = Registry::getValue('sb.jukebox.voting.scale.max');
				$iScale = $iMax - $iMin;
				$iRealVote = round(100 / $iScale * ($iVote));
				
				/*var_dumpp($iVote);
				var_dumpp($iRealVote);
				die();*/
				$this->nodeSubject->removeVote(User::getUUID());
				$this->nodeSubject->placeVote(User::getUUID(), $iRealVote);
				
				if ($_REQUEST->getParam('silent') == NULL) {
					if ($_REQUEST->getParam('target') != NULL) {
						switch ($_REQUEST->getParam('target')) {
							case 'parent':
								$_RESPONSE->redirect($this->nodeSubject->getParent()->getProperty('jcr:uuid'), 'details');
								break;
						}
					} else {
						switch($this->nodeSubject->getPrimaryNodeType()) {
							case 'sb_jukebox:album':
								$_RESPONSE->redirect($this->nodeSubject->getProperty('jcr:uuid'), 'details');
								break;	
							case 'sb_jukebox:artist':
								$_RESPONSE->redirect($this->nodeSubject->getProperty('jcr:uuid'), 'details');
								break;	
							case 'sb_jukebox:track':
								$_RESPONSE->redirect($this->nodeSubject->getProperty('jcr:uuid'), 'details');
								break;
							case 'sb_jukebox:playlist':
								$_RESPONSE->redirect($this->nodeSubject->getProperty('jcr:uuid'), 'details');
								break;
						}
					}
				}
				break;
				
			default:
				throw new sbException(__CLASS__.': action not recognized ('.$sAction.')');
				
		}
		
	}
	
}

?>