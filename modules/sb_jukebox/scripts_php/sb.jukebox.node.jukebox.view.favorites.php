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
class sbView_jukebox_jukebox_favorites extends sbJukeboxView {
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function execute($sAction) {
		
		global $_RESPONSE;
		
		$nodeFavorites = $this->getFavoritesNode();
		
		switch ($sAction) {
			
			case 'display':
				break;
			
			case 'addItem':
				$nodeSubject = $this->crSession->getNodeByIdentifier($_REQUEST->getParam('item'));
				$nodeFavorites->addExistingNode($nodeSubject);
				$nodeFavorites->save();
				break;
				
			case 'removeItem':
				$sItem = $_REQUEST->getParam('item');
				if ($sItem == 'all') {
					foreach ($nodeFavorites->getChildren() as $nodeItem) {
						$this->removeFavorite($nodeItem, $nodeFavorites);
					}
				} else {
					$nodeItem = $this->crSession->getNodeByIdentifier($sItem);
					$this->removeFavorite($nodeItem, $nodeFavorites);
				}
				break;
				
			case 'getM3U':
				$sName = $nodeFavorites->getProperty('name');
				$bRandom = FALSE;
				if ($_REQUEST->getParam('random') != NULL) {
					$bRandom = TRUE;	
				}
				$sPlaylist = $this->getPlaylist($nodeFavorites, $bRandom);
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
		
		$niFavorites = $nodeFavorites->loadChildren('debug', TRUE, TRUE);
		if ($niFavorites->getSize() > 0) {
			$nodeFavorites->storeChildren('debug');
			$_RESPONSE->addData($niFavorites->getElement('favorites'));
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getFavoritesNode() {
		$nodeUser = User::getNode();
		$sJukeboxUUID = $this->getJukebox()->getProperty('jcr:uuid');
		if ($nodeUser->hasNode($sJukeboxUUID)) {
			$nodeFavorites = $nodeUser->getNode($sJukeboxUUID);
		} else {
			$nodeFavorites = $nodeUser->addNode($sJukeboxUUID, 'sb_jukebox:playlist');
			$nodeFavorites->setProperty('label', 'Favorites');
			$nodeUser->save();
		}
		return ($nodeFavorites);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function removeFavorite($nodeItem, $nodeFavorites) {
		foreach ($nodeItem->getSharedSet() as $nodeShared) {
			if ($nodeShared->getParent()->isSame($nodeFavorites)) {
				$nodeShared->removeShare();
				$this->crSession->save();
				return (TRUE);
			}
		}
		return (FALSE);
	}
	
	
}

?>