<?php

//------------------------------------------------------------------------------
/**
*	@package solidMatter[sbSystem]
*	@subpackage Core
*	@author	()((() [Oliver Müller]
*	@version 1.00.00
*/
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
/**
* 
*/
class sbSession {
	
	public static $aData = array();
	
	private static $sSessionID = NULL;
	
	private static $iTimeout = NULL;
	private static $iLifespan = 0;
	private static $iLifetime = 0;
	
	private static $oWatchdog = NULL;
	
	private static $bClosed = FALSE;
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public static function getID() {
		return (self::$sSessionID);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public static function start($sSessionID = NULL, $iTimeout = NULL) {
		if ($sSessionID != NULL) {
			self::$sSessionID = $sSessionID;
		}
		self::loadSession();
		self::$oWatchdog = new sbSessionWatchdog();
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public static function setTimeout($iTimeout) {
		self::$iTimeout = $iTimeout;
	}
	
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public static function commit() {
		self::$oWatchdog->disarm();
		self::storeSession();
		self::$bClosed = TRUE;
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public static function save() {
		self::storeSession();
	}
	
	//--------------------------------------------------------------------------
	/**
	*
	* @param 
	* @return 
	*/
	public static function close() {
		self::$oWatchdog->disarm();
		self::$bClosed = TRUE;
	}
	
	//--------------------------------------------------------------------------
	/**
	* FIXME: 
	* @param 
	* @return 
	*/
	public static function destroy($bSoft = FALSE) {
		self::$oWatchdog->disarm();
		self::$aData = array();
		if ($bSoft) {
			self::save();
		} else {
			self::destroySession();
			self::$bClosed = TRUE;
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public static function addData($sKey, $mData) {
		self::$aData[$sKey] = $mData;
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public static function getData($sKey) {
		if (isset(self::$aData[$sKey])) {
			return (self::$aData[$sKey]);
		}
		return (NULL);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public static function removeData($sKey) {
		if (isset(self::$aData[$sKey])) {
			unset(self::$aData[$sKey]);
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public static function isZombie() {
		if (self::$iLifetime > self::$iLifespan) {
			return (TRUE);
		}
		return (FALSE);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	private static function loadSession() {
		$stmtLoadSession = System::getDatabase()->prepareKnown('sbSystem/session/load');
		$stmtLoadSession->bindParam('session_id', self::$sSessionID, PDO::PARAM_STR);
		$stmtLoadSession->execute();
		foreach ($stmtLoadSession as $aRow) {
			self::$iLifetime = $aRow['lifetime'];
			self::$iLifespan = $aRow['lifespan'];
			self::$aData = unserialize($aRow['data']);
		}
		$stmtLoadSession->closeCursor();
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	private static function storeSession() {
		if (self::$bClosed) {
			throw new sbException('session is closed and cannot be stored');
		}
		$stmtStoreSession = System::getDatabase()->prepareKnown('sbSystem/session/store');
		$stmtStoreSession->bindParam('session_id', self::$sSessionID, PDO::PARAM_STR);
		$stmtStoreSession->bindParam('lifespan', self::$iTimeout, PDO::PARAM_INT);
		$sSerializedData = serialize(self::$aData);
		$stmtStoreSession->bindParam('data', $sSerializedData, PDO::PARAM_STR);
		$stmtStoreSession->execute();
		$stmtStoreSession->closeCursor();
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	private static function destroySession() {
		$stmtDestroySession = System::getDatabase()->prepareKnown('sbSystem/session/destroy');
		$stmtDestroySession->bindParam('session_id', self::$sSessionID, PDO::PARAM_STR);
		$stmtDestroySession->execute();
		$stmtDestroySession->closeCursor();
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	private static function clearSessions() {
		$stmtDestroySession = System::getDatabase()->prepareKnown('sbSystem/session/clear');
		$stmtDestroySession->execute();
		$stmtDestroySession->closeCursor();
	}

}

class sbSessionWatchdog {
	
	private $bArmed = TRUE;
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function __destruct() {
		if ($this->bArmed) {
			sbSession::commit();
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function disarm() {
		$this->bArmed = FALSE;
	}
	
}

?>