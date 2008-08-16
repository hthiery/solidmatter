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
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function execute($sAction) {
		
		global $_RESPONSE;
		
		switch ($sAction) {
			
			case 'display':
				
				// search form
				$formSearch = $this->buildSearchForm('albums');
				$formSearch->saveDOM();
				$_RESPONSE->addData($formSearch);
				
				// comment form
				$formComment = $this->buildCommentForm();
				$formComment->saveDOM();
				$_RESPONSE->addData($formComment);
				
				// add tracks
				$niTracks = $this->nodeSubject->loadChildren('tracks', TRUE, TRUE, TRUE);
				foreach ($niTracks as $nodeTrack) {
					$nodeTrack->getVote($this->getPivotUUID());
				}
				
				// add comments
				$niComments = $this->nodeSubject->loadChildren('comments', TRUE, TRUE, TRUE);
				foreach ($niComments as $nodeComment) {
					// TODO: check user existence, might be deleted
					$nodeUser = $this->crSession->getNodeByIdentifier($nodeComment->getProperty('jcr:createdBy'));
					$nodeComment->setAttribute('username', $nodeUser->getProperty('label'));
				}
				$this->nodeSubject->storeChildren();
				
				// add vote
				$this->nodeSubject->getVote(User::getUUID());
				
				break;
			
			case 'search':
				
				
				break;
			
			case 'addItem':
				if (!User::isAuthorised('edit', $this->nodeSubject)) {
					throw new Exception('You are not allowed to edit this playlist');
				}
				
				$nodeItem = $this->crSession->getNodeByIdentifier($_REQUEST->getParam('item'));
				
				$aTracks = array();
				switch ($nodeItem->getPrimaryNodeType()) {
					case 'sb_jukebox:track':
						$aTracks[] = $nodeItem;
						break;
					case 'sb_jukebox:album':
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
				
			case 'activate':
				if (!User::isAuthorised('edit', $this->nodeSubject)) {
					throw new Exception('You are not allowed to edit this playlist');
				}
				$sJukeboxUUID = $this->getJukebox()->getIdentifier();
				sbSession::$aData['sbJukebox'][$sJukeboxUUID]['playlist'] = $this->nodeSubject->getIdentifier();
				$_RESPONSE->redirect('-', 'playlists');
				break;
			
			case 'getM3U':
				$sName = $this->nodeSubject->getProperty('name');
				$sPlaylist = $this->getPlaylist($this->nodeSubject);
				headers('m3u', array(
					'filename' => $sName.'.m3u',
					'download' => false,
					'size' => strlen($sPlaylist),
				));
				echo $sPlaylist;
				exit();
			
			default:
				throw new sbException(__CLASS__.': action not recognized ('.$sAction.')');
	
		}
		
				
	}
	
}


?>