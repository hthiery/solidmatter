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
class sbView_jukebox_playlist_details extends sbJukeboxView {
	
	protected $aRequiredAuthorisations = array(
		'display' => array('read'),
		'addItem' => array('add_titles'),
		'removeItem' => array('write'),
		'orderBefore' => array('write'),
		'activate' => array('add_titles'),
		'getM3U' => array('read'),
	);
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function execute($sAction) {
		
		global $_RESPONSE;
		$this->checkRequirements($sAction);
		
		switch ($sAction) {
			
			case 'display':
				
				// forms
				$this->addSearchForm('artists');
				$this->addCommentForm();
//				$this->addTagForm();
//				$this->addRelateForm();
				
				// data
				$this->addComments();
				$this->nodeSubject->getVote(User::getUUID());
				
				// add tracks
				$niTracks = $this->nodeSubject->loadChildren('tracks', TRUE, TRUE, FALSE);
				foreach ($niTracks as $nodeTrack) {
					$nodeTrack->getVote($this->getPivotUUID());
				}
				
				// save data in element
				$this->nodeSubject->storeChildren();
				break;
			
			case 'search':
				throw new LazyBastardException('searching playlists not implemented yet');
				break;
			
			case 'addItem':
				
				$nodeItem = $this->crSession->getNodeByIdentifier($_REQUEST->getParam('item'));
				
				$aTracks = array();
				switch ($nodeItem->getPrimaryNodeType()) {
					case 'sbJukebox:Track':
						$aTracks[] = $nodeItem;
						break;
					case 'sbJukebox:Album':
						$niTracks = $nodeItem->getChildren('play');
						foreach ($niTracks as $nodeTrack) {
							$aTracks[] = $nodeTrack;
						}
						break;
					default:
						throw new sbException('You can only add Albums and Tracks right now');
						break;
				}
				
				foreach ($aTracks as $nodeTrack) {
					$this->nodeSubject->addExistingNode($nodeTrack);
				}
				$this->nodeSubject->save();
				
				$_RESPONSE->redirect($this->nodeSubject->getIdentifier());
				
				break;
				
			case 'removeItem':
				$nodeItem = $this->crSession->getNodeByIdentifier($_REQUEST->getParam('item'));
				foreach ($nodeItem->getSharedSet() as $nodeShared) {
					if ($nodeShared->getParent()->isSame($this->nodeSubject)) {
						$nodeShared->removeShare();
						$this->crSession->save();
					}
				}
				if (!isset($_GET['silent'])) {
					$_RESPONSE->redirect($this->nodeSubject->getIdentifier());
				}
				break;
				
			case 'orderBefore':
				$nodeSubject = $this->crSession->getNodeByIdentifier($_REQUEST->getParam('subject'));
				$nodeNextSibling = $this->crSession->getNodeByIdentifier($_REQUEST->getParam('nextsibling'));
				$this->nodeSubject->orderBefore($nodeSubject->getName(), $nodeNextSibling->getName());
				$this->nodeSubject->save();
				break;
				
			case 'activate':
				$sJukeboxUUID = $this->getJukebox()->getIdentifier();
				sbSession::$aData['sbJukebox'][$sJukeboxUUID]['playlist'] = $this->nodeSubject->getIdentifier();
				$_RESPONSE->redirect('-', 'playlists');
				break;
			
			case 'getM3U':
				$this->sendPlaylist();
				break;
			
			default:
				throw new sbException(__CLASS__.': action not recognized ('.$sAction.')');
	
		}
		
				
	}
	
}


?>