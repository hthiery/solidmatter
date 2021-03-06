<?php

//------------------------------------------------------------------------------
/**
* @package solidMatter[sbCR]
* @author	()((() [Oliver Müller]
* @version 1.00.00
*/
//------------------------------------------------------------------------------

import('sb.pdo.repository.queries');
import('sb.cr');
import('sb.cr.propertydefinitioncache');

// xml file containing all information on repositories this sbCR instance supports
if (!defined('REPOSITORY_DEFINITION_FILE')) {	define('REPOSITORY_DEFINITION_FILE', 'repositories.xml'); }
// number of characters to use for the pseudo-materialized path on each level
if (!defined('REPOSITORY_MPHASH_SIZE')) {		define('REPOSITORY_MPHASH_SIZE', 5); }

//------------------------------------------------------------------------------
/**
*/
class sbCR_Repository {
	
	protected $sID = NULL;
	protected $sPrefix = NULL;
	
	protected $aDescriptors = array(
		'SPEC_VERSION_DESC' => '1.0',
		'SPEC_NAME_DESC' => 'solidbytes Content Repository for PHP Technology API',
		'REP_VENDOR_DESC' => 'solidbytes',
		'REP_VENDOR_URL_DESC' => 'http://www.solidbytes.net',
		'REP_NAME_DESC' => 'sbCR',
		'REP_VERSION_DESC' => '1.0',
		'LEVEL_1_SUPPORTED' => 'true',
		'LEVEL_2_SUPPORTED' => 'true',
		'OPTION_TRANSACTIONS_SUPPORTED' => 'true',
		'OPTION_VERSIONING_SUPPORTED' => 'false',
		'OPTION_OBSERVATION_SUPPORTED' => 'false',
		'OPTION_LOCKING_SUPPORTED' => 'false',
		'OPTION_LIFECYCLE_SUPPORTED' => 'true',
		'OPTION_QUERY_SQL_SUPPORTED' => 'false',
		'QUERY_XPATH_POS_INDEX' => 'false',
		'QUERY_XPATH_DOC_ORDER' => 'false',
	);
	
	// basic info about existing repositories 
	private $elemRepositoryDefinition = NULL;
	
	private $cacheRepository = NULL;
	private $aRepositoryInformation = array();
	
	private $DB = NULL;
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function __construct(sbPDO $DB, string $sRepositoryID, string $sRepositoryPrefix) {
		
		// store ID for later use
		$this->sID = $sRepositoryID;
		$this->sPrefix = $sRepositoryPrefix;
		$this->DB = $DB;
		$this->DB->setRewrite('{PREFIX_REPOSITORY}', $sRepositoryPrefix);
		
		$this->elemRepositoryDefinition = CONFIG::getRepositoryConfig($sRepositoryID);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getID() {
		return ($this->sID);
	}
	
	//--------------------------------------------------------------------------
	/**
	 *
	 * @param
	 * @return
	 */
	public function getPrefix() {
		return ($this->sPrefix);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getDescriptor(string $sKey) {
		if (isset($this->aDescriptors[$sKey])) {
			return ($this->aDescriptors[$sKey]);
		} else {
			return (NULL);
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @return 
	*/
	public function getDescriptorKeys() : array {
		return (array_keys($this->aDescriptors));
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param sbCR_Credentials 
	* @return 
	*/
	public function login(sbCR_Credentials $crCredentials = NULL, string $sWorkspaceID = NULL) : sbCR_Session {
		
		// credentials are mandatory - but checks are disabled for now
// 		if ($crCredentials == NULL) {
// 			throw new RepositoryException('credentials missing');
// 		}
		
		if ($sWorkspaceID != NULL) {
		
			// check if workspace exists
			$elemWorkspace = NULL;
			foreach ($this->elemRepositoryDefinition->xpath('workspace') as $elemCurrentWorkspace) {
				if ($elemCurrentWorkspace['id'] == $sWorkspaceID) {
					$elemWorkspace = $elemCurrentWorkspace;
					$sWorkspacePrefix = (string) $elemCurrentWorkspace['prefix'];
				}
			}
			if ($elemWorkspace == NULL) {
				throw new NoSuchWorkspaceException('workspace "'.$sWorkspaceID.'" not in repository "'.$this->elemRepositoryDefinition['id'].'"');
			}
			
// 			// check authorisation - disabled for now
// 			foreach ($elemWorkspace->user as $elemUser) {
// 				// TODO: really check permissions, not only user existence!
// 				if ($elemUser['name'] != $crCredentials->getUserID() || $elemUser['pass'] != $crCredentials->getPassword()) {
// 					throw new AccessDeniedException('provided user is not authorised to access workspace "'.$sWorkspaceID.'" in repository "'.$this->elemRepositoryDefinition['id'].'"');
// 				}
// 			}
			
		}
		
		// load and store repository infos if necessary
		/*$this->cacheRepository = CacheFactory::getInstance('repository');
		if (FALSE || $this->cacheRepository->exists($this->sRepositoryID)) {
			$this->aRepositoryInformation = $this->cacheRepository->loadData($this->sRepositoryID);
		} else {
			$this->gatherRepositoryInformation();
		}*/
		
		$crSession = new sbCR_Session($this->DB, $crCredentials, $this, $sWorkspaceID, $sWorkspacePrefix);
		
		return ($crSession);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	 *
	 * @param 
	 * @return
	 */
	public function createWorkspace(string $sWorkspaceID, string $sWorkspacePrefix) {
		
		import('sb.pdo.setup.queries.workspaces');
		
		$this->DB->setRewrite('{PREFIX_WORKSPACE}', $sWorkspacePrefix);
		$stmtCreate = $this->DB->prepareKnown('sbCR/workspace/createTables');
		$stmtCreate->execute();
		$stmtCreate->closeCursor();
		$stmtInit = $pdoRepository->prepareKnown('sbCR/workspace/createEntries');
		$stmtInit->execute();
		$stmtInit->debug();
		$stmtInit->closeCursor();
// 		CONFIG::addWorkspace($sRepositoryID, $sWorkspaceID, $sWorkspacePrefix);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function gatherRepositoryInformation() : DOMDocument {
		
		$sRepository = (string) $this->elemRepositoryDefinition['id'];
		$aRepositoryInfo = array();
		
		// get nodetypes
		$stmtNodetypes = $this->DB->prepareKnown('sbCR/repository/getNodeTypes');
		$stmtNodetypes->execute();
		$stmtNodetypes = $stmtNodetypes->fetchAll(PDO::FETCH_ASSOC);
		foreach ($stmtNodetypes as $aRow) {
			$aRepositoryInfo[$aRow['s_type']]['details'] = $aRow;
		}
		
		// get views
		$aViews = array();
		$stmtViews = $this->DB->prepareKnown('sb_system/repository/getViews');
		$stmtViews->execute();
		$stmtViews = $stmtViews->fetchAll(PDO::FETCH_ASSOC);
		foreach ($stmtViews as $aRow) {
			$aRepositoryInfo[$aRow['fk_nodetype']]['views'][$aRow['s_view']]['details'] = $aRow;
		}
		
		// get views
		$aActions = array();
		$stmtActions = $this->DB->prepareKnown('sb_system/repository/getActions');
		$stmtActions->execute();
		$stmtActions = $stmtActions->fetchAll(PDO::FETCH_ASSOC);
		foreach ($stmtActions as $aRow) {
			$aRepositoryInfo[$aRow['fk_nodetype']]['views'][$aRow['s_view']]['actions'][$aRow['s_action']]['details'] = $aRow;
		}
		
		// get nodetype hierarchy
		$aHierarchy = array();
		$stmtHierarchy = $this->DB->prepareKnown('sbCR/repository/getNodeTypeHierarchy');
		$stmtHierarchy->execute();
		$aHierarchy = $stmtHierarchy->fetchAll(PDO::FETCH_ASSOC);
		
		// create DOM and store XML 
		$domReposInfo = new sbDOMDocument('1.0');
		$elemRoot = $domReposInfo->createElement('repository');
		$elemNodetypes = $domReposInfo->createElement('nodetypes');
		
		foreach ($aRepositoryInfo as $sNodetype => $aNodetype) {
			if (!isset($aNodetype['details'])) {
				continue;
			}
			$elemNodetype = $domReposInfo->createElement('nodetype');
			foreach ($aNodetype['details'] as $sKey => $sValue) {
				$elemNodetype->setAttribute($sKey, $sValue);
			}
			foreach ($aHierarchy as $aEntry) {
				if ($aEntry['fk_childnodetype'] == $sNodetype) {
					$elemParent = $domReposInfo->createElement('parent', $aEntry['fk_parentnodetype']);
					$elemNodetype->appendChild($elemParent);
				}
			}
			$elemViews = $domReposInfo->createElement('views');
			if (isset($aNodetype['views'])) {
				foreach ($aNodetype['views'] as $sView => $aView) {
					if (!isset($aView['details'])) {
						//var_dumpp($sView);
						continue;
					}
					$elemView = $domReposInfo->createElement('view');
					foreach ($aView['details'] as $sKey => $sValue) {
						$elemView->setAttribute($sKey, $sValue);
					}
					$elemActions = $domReposInfo->createElement('actions');
					if (isset($aView['actions'])) {
						foreach ($aView['actions'] as $aAction) {
							$elemAction = $domReposInfo->createElement('action');
							foreach ($aAction['details'] as $sKey => $sValue) {
								$elemAction->setAttribute($sKey, $sValue);
							}
							$elemActions->appendChild($elemAction);
						}
						$elemView->appendChild($elemActions);
					}
					$elemViews->appendChild($elemView);
				}
			}
			$elemNodetype->appendChild($elemViews);
			$elemNodetypes->appendChild($elemNodetype);
		}
		
		$elemRoot->appendChild($elemNodetypes);
		$domReposInfo->appendChild($elemRoot);
		
		return ($domReposInfo);
	}
	
	//--------------------------------------------------------------------------
	/**
	 * Add/Update or delete repository definitions.
	 * TODO: this is not compliant with JCR - needs to be converted to the appropriate XYZTemplates and logic.
	 * @param string The repository aspect to change (nodetype/view/action/property/..)
	 * @param string The action to execute (add/modify/remove)
	 * @param array The definition data for the current aspect, needs to be complete
	 * @return
	 */
	public function changeRepositoryDefinition(string $sType, string $sMode = 'add', array $aData = NULL) {
		
		import("sb.pdo.repository.queries.administration");
		
		switch ($sType) {
			
			case 'begin';
				$this->DB->beginTransaction('changeRepository');
				break;
				
			case 'commit':
				$this->DB->commit('changeRepository');
				break;
				
			case 'nodetype':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/nodetype/save');
					$stmtAdd->bindParam('nodetype', $aData['nodetype']);
					$stmtAdd->bindParam('class', $aData['class']);
					$stmtAdd->bindParam('classfile', $aData['classfile']);
					$stmtAdd->bindParam('type', $aData['type']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/nodetype/remove');
					$stmtRemove->bindParam('nodetype', $aData['nodetype']);
					$stmtRemove->execute();
				}
				break;
			
			case 'view':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/view/save');
					$stmtAdd->bindParam('nodetype', $aData['nodetype']);
					$stmtAdd->bindParam('view', $aData['view']);
					$stmtAdd->bindParam('display', $aData['display']);
					$stmtAdd->bindParam('labelpath', $aData['labelpath']);
					$stmtAdd->bindParam('class', $aData['class']);
					$stmtAdd->bindParam('classfile', $aData['classfile']);
					$stmtAdd->bindParam('order', $aData['order']);
					$stmtAdd->bindParam('priority', $aData['priority']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/view/remove');
					$stmtRemove->bindParam('nodetype', $aData['nodetype']);
					$stmtRemove->bindParam('view', $aData['view']);
					$stmtRemove->execute();
				}
				break;
				
			case 'action':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/action/save');
					$stmtAdd->bindParam('nodetype', $aData['nodetype']);
					$stmtAdd->bindParam('view', $aData['view']);
					$stmtAdd->bindParam('action', $aData['action']);
					$stmtAdd->bindParam('default', $aData['default']);
					$stmtAdd->bindParam('class', $aData['class']);
					$stmtAdd->bindParam('classfile', $aData['classfile']);
					$stmtAdd->bindParam('outputtype', $aData['outputtype']);
					$stmtAdd->bindParam('stylesheet', $aData['stylesheet']);
					$stmtAdd->bindParam('mimetype', $aData['mimetype']);
					$stmtAdd->bindParam('uselocale', $aData['uselocale']);
					$stmtAdd->bindParam('isrecallable', $aData['isrecallable']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/action/remove');
					$stmtRemove->bindParam('nodetype', $aData['nodetype']);
					$stmtRemove->bindParam('view', $aData['view']);
					$stmtRemove->bindParam('action', $aData['action']);
					$stmtRemove->execute();
				}
				break;
				
			case 'inheritance':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/hierarchy/save');
					$stmtAdd->bindParam('parentnodetype', $aData['parentnodetype']);
					$stmtAdd->bindParam('childnodetype', $aData['childnodetype']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/hierarchy/remove');
					$stmtRemove->bindParam('parentnodetype', $aData['parentnodetype']);
					$stmtRemove->bindParam('childnodetype', $aData['childnodetype']);
					$stmtRemove->execute();
				}
				break;
				
			case 'property':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/property/save');
					$stmtAdd->bindParam('nodetype', $aData['nodetype']);
					$stmtAdd->bindParam('attributename', $aData['attributename']);
					$stmtAdd->bindParam('type', $aData['type']);
					$stmtAdd->bindParam('internaltype', $aData['internaltype']);
					$stmtAdd->bindParam('showinproperties', $aData['showinproperties']);
					$stmtAdd->bindParam('labelpath', $aData['labelpath']);
					$stmtAdd->bindParam('storagetype', $aData['storagetype']);
					$stmtAdd->bindParam('auxname', $aData['auxname']);
					$stmtAdd->bindParam('order', $aData['order']);
					$stmtAdd->bindParam('protected', $aData['protected']);
					$stmtAdd->bindParam('protectedoncreation', $aData['protectedoncreation']);
					$stmtAdd->bindParam('multiple', $aData['multiple']);
					$stmtAdd->bindParam('defaultvalues', $aData['defaultvalues']);
					$stmtAdd->bindParam('descriptionpath', $aData['descriptionpath']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/property/remove');
					$stmtRemove->bindParam('nodetype', $aData['nodetype']);
					$stmtRemove->bindParam('attributename', $aData['attributename']);
					$stmtRemove->execute();
				}
				break;
			
			case 'authorisation':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/authorisation/save');
					$stmtAdd->bindParam('nodetype', $aData['nodetype']);
					$stmtAdd->bindParam('authorisation', $aData['authorisation']);
					$stmtAdd->bindParam('parentauthorisation', $aData['parentauthorisation']);
					$stmtAdd->bindParam('default', $aData['default']);
					$stmtAdd->bindParam('order', $aData['order']);
					$stmtAdd->bindParam('onlyfrontend', $aData['onlyfrontend']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/authorisation/remove');
					$stmtAdd->bindParam('nodetype', $aData['nodetype']);
					$stmtAdd->bindParam('authorisation', $aData['authorisation']);
					$stmtRemove->execute();
				}
				break;
				
			case 'viewauthorisation':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/viewauthorisation/save');
					$stmtAdd->bindParam('nodetype', $aData['nodetype']);
					$stmtAdd->bindParam('view', $aData['view']);
					$stmtAdd->bindParam('action', $aData['action']);
					$stmtAdd->bindParam('authorisation', $aData['authorisation']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/viewauthorisation/remove');
					$stmtRemove->bindParam('nodetype', $aData['nodetype']);
					$stmtRemove->bindParam('view', $aData['view']);
					$stmtRemove->bindParam('action', $aData['action']);
					$stmtRemove->execute();
				}
				break;
				
			case 'mode':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/mode/save');
					$stmtAdd->bindParam('mode', $aData['mode']);
					$stmtAdd->bindParam('parentnodetype', $aData['parentnodetype']);
					$stmtAdd->bindParam('childnodetype', $aData['childnodetype']);
					$stmtAdd->bindParam('display', $aData['display']);
					$stmtAdd->bindParam('choosable', $aData['choosable']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/mode/remove');
					$stmtRemove->bindParam('mode', $aData['mode']);
					$stmtRemove->bindParam('parentnodetype', $aData['parentnodetype']);
					$stmtRemove->bindParam('childnodetype', $aData['childnodetype']);
					$stmtRemove->execute();
				}
				break;
			
			case 'relation':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/ontology/save');
					$stmtAdd->bindParam('relation', $aData['relation']);
					$stmtAdd->bindParam('sourcenodetype', $aData['sourcenodetype']);
					$stmtAdd->bindParam('targetnodetype', $aData['targetnodetype']);
					$stmtAdd->bindParam('reverserelation', $aData['reverserelation']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/ontology/remove');
					$stmtRemove->bindParam('relation', $aData['relation']);
					$stmtRemove->bindParam('sourcenodetype', $aData['sourcenodetype']);
					$stmtRemove->bindParam('targetnodetype', $aData['targetnodetype']);
					$stmtRemove->execute();
				}
				break;
				
			case 'lifecycle':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/lifecycle/save');
					$stmtAdd->bindParam('nodetype', $aData['nodetype']);
					$stmtAdd->bindParam('state', $aData['state']);
					$stmtAdd->bindParam('statetransition', $aData['statetransition']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/lifecycle/remove');
					$stmtRemove->bindParam('nodetype', $aData['nodetype']);
					$stmtRemove->bindParam('state', $aData['state']);
					$stmtRemove->bindParam('statetransition', $aData['statetransition']);
					$stmtRemove->execute();
				}
				break;
				
			case 'registry':
				if ($sMode == 'add' || $sMode == 'modify') {
					$stmtAdd = $this->DB->prepareKnown('sbCR/registry/save');
					$stmtAdd->bindParam('key', $aData['key']);
					$stmtAdd->bindParam('type', $aData['type']);
					$stmtAdd->bindParam('internaltype', $aData['internaltype']);
					$stmtAdd->bindParam('userspecific', $aData['userspecific']);
					$stmtAdd->bindParam('defaultvalue', $aData['defaultvalue']);
					$stmtAdd->bindParam('comment', $aData['comment']);
					$stmtAdd->execute();
				} else {
					$stmtRemove = $this->DB->prepareKnown('sbCR/registry/remove');
					$stmtRemove->bindParam('key', $aData['key']);
					$stmtRemove->execute();
				}
				break;
			
			default:
				throw new sbException(__CLASS__.': change type not supported "'.$sType.'"');
				
			
		}
		
	}
	
}

?>