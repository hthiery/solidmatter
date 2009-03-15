<?php

//------------------------------------------------------------------------------
/**
*	@package solidMatter[sbSystem]
*	@subpackage Tools
*	@author	()((() [Oliver Müller]
*	@version 1.00.00
*/
//------------------------------------------------------------------------------

import('sb.tools.filesystem');
import('sb.tools.filesystem.object');
import('sb.tools.filesystem.file');

//------------------------------------------------------------------------------
/**
*/
class sbDirectory extends sbFilesystemObject {
	
	// child directories
	protected $aDirectories = array();
	protected $aDirectoriesBackup = array();
	
	// child files
	protected $aFiles = array();
	protected $aFilesBackup = array();
	
	// are the sizes already examined?
	protected $bSizesRead = FALSE;
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function __init($sRelPath) {
		parent::__init($sRelPath);
		$this->read();
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function read($bSkipParents = TRUE) {
		
		// checks
		if ($this->aInfo['abs_path'] == NULL) {
			throw new sbException(__CLASS__.': directory not set');
		}
		if (!file_exists($this->aInfo['abs_path']) || !is_dir($this->aInfo['abs_path'])) {
			throw new sbException(__CLASS__.': directory "'.$this->aInfo['abs_path'].'" does not exist');
		}
		
		// clear in case read() is called multiple times
		$this->aDirectories = array();
		$this->aDirectoriesBackup = array();
		$this->aFiles = array();
		$this->aFilesBackup = array();
		
		// read and store
		$aEntries = scandir($this->aInfo['abs_path']);
		foreach ($aEntries as $sEntry) {
			if (is_dir($this->aInfo['abs_path'].$sEntry)) {
				if ($bSkipParents) {
					if ($sEntry == '.' || $sEntry == '..') {
						continue;
					}
				}
				$this->aDirectories[] = array('name' => $sEntry);
			} else {
				$this->aFiles[] = array('name' => $sEntry);
			}
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function readSizes($bIncludeDirs = FALSE) {
		
		// get file sizes
		foreach ($this->aFiles as $iIndex => $aFile) {
			$this->aFiles[$iIndex]['size'] = filesize($this->aInfo['abs_path'].$aFile['name']);
			$this->aFiles[$iIndex]['hrsize'] = filesize2display($this->aFiles[$iIndex]['size']);
		}
		
		// get optional dirsize
		if ($bIncludeDirs) {
			foreach ($this->aDirectories as $iIndex => $aDirectory) {
				$drCurrent = new sbDirectory($this->aInfo['abs_path'].$aDirectory['name'].'/');
				$this->aDirectories[$iIndex]['size'] = $drCurrent->getAccumulatedSize();
				$this->aDirectories[$iIndex]['hrsize'] = filesize2display($this->aDirectories[$iIndex]['size']);
			}
		}
		
		$this->bSizesRead = TRUE;
		
		$this->aDirectory['size'] = $this->getAccumulatedSize();
		$this->aDirectory['hrsize'] = filesize2display($this->aDirectory['size']);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getAccumulatedSize() {
		
		if (!$this->bSizesRead) {
			$this->readSizes();
		}
		
		$iSize = 0;
		foreach ($this->aFiles as $aFile) {
			$iSize += $aFile['size'];
		}
		
		return ($iSize);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function filterFiles($sRegEx, $bExcludeMatches = FALSE) {
		
		foreach ($this->aFiles as $iIndex => $aFile) {
			if ($bExcludeMatches && preg_match($sRegEx, $aFile['name'])) {
				unset($this->aFiles[$iIndex]);
			}
			if (!$bExcludeMatches && !preg_match($sRegEx, $aFile['name'])) {
				unset($this->aFiles[$iIndex]);
			}
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function countFiles() {
		return (count($this->aFiles));
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function resetFilter() {
		$this->aFiles = $this->aFilesBackup;
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getFiles($bAsFiles = FALSE) {
		
		$aFiles = array();
		
		foreach ($this->aFiles as $aFile) {
			if ($bAsFiles) {
				$aFiles[] = $this->getFile($aFile['name']);
			} else {
				$aFiles[] = $aFile['name'];
			}
		}
		
		return ($aFiles);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getFile($sRelPath) {
		
		// fill search array if necessary
		$aSearch = array();
		if (!is_array($sRelPath)) {
			$aSearch[0] = $sRelPath;
		} else {
			$aSearch = $sRelPath;
		}
		
		foreach ($aSearch as $sRelPath) {
			if (file_exists($this->aInfo['abs_path'].$sRelPath)) {
				return (new sbFile($this->aInfo['abs_path'].$sRelPath));
			}
		}
		
		return (FALSE);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function hasFile($sRelPath) {
		foreach ($this->aFiles as $aFileInfo) {
			if ($aFileInfo['name'] == $sRelPath) {
				return (TRUE);
			}
		}
		return (FALSE);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getDirectories($bAsDirectoryReaders = FALSE) {
		
		$aDirectories = array();
		
		if (!$bAsDirectoryReaders) {
			foreach ($this->aDirectories as $aDirectory) {
				$aDirectories[] = $aDirectory['name'];
			}
		} else {
			foreach ($this->aDirectories as $aDirectory) {
				$aDirectories[] = new sbDirectory($this->aInfo['abs_path'].$aDirectory['name']);
			}
		}
		
		return ($aDirectories);
	
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function sort($sSortcriterium = 'name') {
		import('sb.tools.arrays');
		ivsort($this->aFiles, $sSortcriterium, TRUE);
		ivsort($this->aFiles, $sSortcriterium, TRUE);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getElement($sContainerName, $bIncludeDirs = FALSE) {
		
		$this->aInfo['totalfiles'] = count($this->aFiles);
		$this->aInfo['totaldirs'] = count($this->aDirectories);
		
		$domFiles = new DOMDocument();
		$elemContainer = $domFiles->createElement($sContainerName);
		foreach ($this->aInfo as $sKey => $sValue) {
			$elemContainer->setAttribute($sKey, $sValue);
		}
		
		// generate dir elements
		if ($bIncludeDirs) {
			$elemDirectories = $domFiles->createElement('directories');
			$elemDirectories->setAttribute('count', count($this->aDirectories));
			foreach ($this->aDirectories as $aDirectory) {
				$elemDir = $domFiles->createElement('directory');
				foreach ($aDirectory as $sAttribute => $sValue) {
					$elemDir->setAttribute($sAttribute, $sValue);	
				}
				$elemContainer->appendChild($elemDir);
			}
		}
		
		// generate file elements
		$elemFiles = $domFiles->createElement('files');
		$elemFiles->setAttribute('count', count($this->aFiles));
		foreach ($this->aFiles as $aFile) {
			$elemFile = $domFiles->createElement('file');
			foreach ($aFile as $sAttribute => $sValue) {
				$elemFile->setAttribute($sAttribute, $sValue);
			}
			$elemFiles->appendChild($elemFile);
		}
		$elemContainer->appendChild($elemFiles);
		
		return ($elemContainer);
		
	}
	
}

?>