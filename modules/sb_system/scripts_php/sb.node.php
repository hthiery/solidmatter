<?php

//------------------------------------------------------------------------------
/**
*	@package solidMatter[sbCR]
*	@author	()((() [Oliver Müller]
*	@version 1.00.00
*/
//------------------------------------------------------------------------------

import('sb.cr.node');
import('sb.pdo.system.queries');

//------------------------------------------------------------------------------
/**
*/
class sbNode extends sbCR_Node {
	
	protected $crNodetype = NULL;
	
	protected $aViews				= array();
	protected $aActions				= array();
	protected $elemViews			= NULL;
	
	public $aChildNodes				= array();
	public $niAncestors				= NULL;
	public $niParents				= NULL;
	
	protected $aAggregatedAuthorisations = NULL;
	protected $aSupportedAuthorisations = NULL;
	protected $aInheritedAuthorisations = NULL;
	protected $aLocalAuthorisations = NULL;
	
	//protected $elemLocalAuthorisations = NULL;
	//protected $elemInheritedAuthorisations = NULL;
	
	protected $aVotes 				= NULL;
	protected $aVoteChanges			= array();
	
	protected $aTags 				= NULL;
	protected $aNewTags				= array();
	protected $aBranchTags			= NULL;
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function __setQueries() {
		
		parent::__setQueries();
		
		// information about the node type
		$this->aQueries['actions/getDetails/given']					= 'sbSystem/node/loadActionDetails/given';
		$this->aQueries['actions/getDetails/default']				= 'sbSystem/node/loadActionDetails/default';
		//$this->aQueries['sbSystem/node/getAllowedSubtypes']		= 'sbSystem/node/getAllowedSubtypes';
		
		$this->aQueries['voting/placeVote']							= 'sbSystem/voting/placeVote';
		$this->aQueries['voting/removeVote']						= 'sbSystem/voting/removeVote';
		$this->aQueries['voting/getUserVote']						= 'sbSystem/voting/getVote/byUser';
		$this->aQueries['voting/getAverageVote']					= 'sbSystem/voting/getVote/average';
		$this->aQueries['voting/getAllVotes']						= 'sbSystem/voting/getVotes';
		
		$this->aQueries['tagging/addTagToNode']						= 'sbSystem/tagging/node/addTag';
		$this->aQueries['tagging/removeTagFromNode']				= 'sbSystem/tagging/node/removeTag';
		$this->aQueries['tagging/removeTagsFromNode']				= 'sbSystem/tagging/node/removeTags';
		$this->aQueries['tagging/getAllNodeTags']					= 'sbSystem/tagging/node/getTags';
		$this->aQueries['tagging/getAllBranchTags']					= 'sbSystem/tagging/node/getBranchTags';
		$this->aQueries['tagging/getTagID']							= 'sbSystem/tagging/tags/getID';
		$this->aQueries['tagging/getTag']							= 'sbSystem/tagging/tags/getTag';
		$this->aQueries['tagging/createNewTag']						= 'sbSystem/tagging/tags/addTag';
		$this->aQueries['tagging/getAllTags']						= 'sbSystem/tagging/tags/getAll';
		$this->aQueries['tagging/increasePopularity']				= 'sbSystem/tagging/tags/increasePopularity';
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function __init() {
		parent::__init();
	}
	
	//--------------------------------------------------------------------------
	// save/move
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function addSaveTask($sTaskType, $aOptions = NULL) {
		switch ($sTaskType) {
			case 'add_tag':
			case 'remove_tag':
				$this->aSaveTasks[$sTaskType][] = $aOptions;
				break;
			default:
				parent::addSaveTask($sTaskType, $aOptions);
		}
		$this->crSession->addSaveTask('save_node', array('subject' => $this));
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function save() {
		
		// first process parent tasks
		$this->crSession->beginTransaction('sbNode::save');
		parent::save();
		
		// anything to do?
		if (count($this->aSaveTasks) == 0) {
			$this->crSession->commit('sbNode::save');
			return (FALSE);
		}
		
		// TODO: first cycle tag and vote tasks to eliminate dupes and unnessessary steps
		
		// work sbNode tasks
		foreach ($this->aSaveTasks as $sTaskType => $aOptions) {
			
			switch ($sTaskType) {
				
				case 'add_tag':
					
					foreach ($aOptions as $iKey => $aDetails) {
						
						$sTag = $aDetails['tag'];
						$iTagID = $this->getTagID($sTag);
						if (!$iTagID) {
							$iTagID = $this->createNewTag($sTag);
						}
						$stmtAddTag = $this->prepareKnown('tagging/addTagToNode');
						$stmtAddTag->bindValue('subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
						$stmtAddTag->bindValue('tag_id', $iTagID, PDO::PARAM_INT);
						$stmtAddTag->execute();
						$this->aTags[$sTag] = TRUE;
						
						unset($this->aNewTags[$sTag]);
						unset($this->aSaveTasks['add_tag'][$iKey]);
						
					}
					
					unset($this->aSaveTasks['add_tag']);
					
					break;
				
				case 'remove_tag':
				
					foreach ($aOptions as $iKey => $aDetails) {
						
						$sTag = $aDetails['tag'];
						$iTagID = $this->getTagID($sTag);
						if (!$iTagID) {
							throw new sbException('tag "'.$sTag.'" does not exist');
						}
						$stmtRemove = $this->prepareKnown($this->aQueries['voting']['removeVote']);
						$stmtRemove->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
						$stmtRemove->bindValue(':tag_id', $iTagID, PDO::PARAM_STR);
						$stmtRemove->execute();
						
						unset($this->aTags[$sTag]);
						unset($this->aSaveTasks['remove_tag'][$iKey]);
						
					}
					
					unset($this->aSaveTasks['remove_tag']);
					break;
					
			}
		}
		
		$this->crSession->commit('sbNode::save');
		
		return (TRUE);
		
	}
	
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM:
	* @param 
	* @return 
	*/
	public function getElement($bDeep = FALSE, $bUseContainer = FALSE) {
		
		$elemSubject = $this->elemSubject->cloneNode(TRUE);
		foreach ($this->aAppendedElements as $elemCurrent) {
			$elemSubject->appendChild($elemCurrent);
		}
		
		foreach ($this->aChildNodes as $sMode => $niCurrentChildren) {
			//var_dumpp($sMode);
			if (!$niCurrentChildren->isEmpty() && $bDeep) {
				//var_dumpp($sMode);
				if ($bUseContainer) {
					$elemContainer = $this->elemSubject->ownerDocument->createElement('children');
					$elemContainer->setAttribute('mode', $sMode);
					$elemSubject->appendChild($elemContainer);
				} else {
					$elemContainer = $elemSubject;
				}
				$elemContainer = $this->elemSubject->ownerDocument->createElement('children');
				$elemContainer->setAttribute('mode', $sMode);
				$elemSubject->appendChild($elemContainer);
				foreach ($niCurrentChildren as $nodeChild) {
					$elemContainer->appendChild($nodeChild->getElement($bDeep, $bUseContainer, $sMode));
				}
			}
		}
		
		if (is_array($this->aTags) && count($this->aTags) > 0) {
			$elemTags = $this->elemSubject->ownerDocument->createElement('tags');
			foreach ($this->aTags as $sTag => $iTagID) {
				$elemTag = $this->elemSubject->ownerDocument->createElement('tag', htmlspecialchars($sTag));
				$elemTag->setAttribute('id', $iTagID);
				$elemTags->appendChild($elemTag);
			}
			$elemSubject->appendChild($elemTags);
		}
		
		if (is_array($this->aBranchTags) && count($this->aBranchTags) > 0) {
			$elemTags = $this->elemSubject->ownerDocument->createElement('branchtags');
			foreach ($this->aBranchTags as $iTagID => $aDetails) {
				$elemTag = $this->elemSubject->ownerDocument->createElement('tag', htmlspecialchars($aDetails['tag']));
				$elemTag->setAttribute('id', $iTagID);
				$elemTag->setAttribute('popularity', $aDetails['popularity']);
				$elemTag->setAttribute('numitems', $aDetails['numitems']);
				$elemTag->setAttribute('customweight', $aDetails['customweight']);
				$elemTags->appendChild($elemTag);
			}
			$elemSubject->appendChild($elemTags);
		}
		
		return ($elemSubject);
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM:
	* @param 
	* @return 
	*/
	public function getModule() {
		return(substr($this->getPrimaryNodeType(), 0, strpos($this->getPrimaryNodeType(), ':')));
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM: 
	* @param 
	* @return 
	*/
	public function gatherContent($bPreview = TRUE) {
		$this->loadChildren('gatherContent', TRUE, TRUE, TRUE);
		//$this->storeChildren();
		//$this->setDeepMode(TRUE);
		foreach ($this->aChildNodes['gatherContent'] as $nodeChild) {
			$nodeChild->gatherContent($bPreview);
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM: 
	* @param 
	* @return 
	*/
	public function appendElement($elemData) {
		$elemImported = $this->elemSubject->ownerDocument->importNode($elemData, TRUE);
		$this->aAppendedElements[] = $elemImported;
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM:
	* @param 
	* @return 
	*/
	public function storeChildren($bUseContainer = TRUE) {
		foreach ($this->aChildNodes as $sMode => $niChildren) {
			$this->storeNodeList($niChildren, $bUseContainer, 'children', $sMode);
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM:
	* @param 
	* @return 
	*/
	public function storeAncestors($bUseContainer = TRUE, $bReverse = FALSE) {
		$this->storeNodeList($this->niAncestors, $bUseContainer, 'ancestors', NULL, $bReverse);
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM:
	* @param 
	* @return 
	*/
	public function storeParents($bUseContainer = TRUE) {
		$this->storeNodeList($this->niParents, $bUseContainer, 'parents');
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM: 
	* @param 
	* @return 
	*/
	protected function storeNodeList($niList, $bUseContainer = FALSE, $sContainerName = 'nodelist', $sMode = NULL, $bReverse = FALSE) {
		
		if ($sMode !== NULL) {
			$bUseContainer = TRUE;
		}
		
		if ($niList != NULL) {
			
			if ($bUseContainer) {
				$elemParent = $this->elemSubject->ownerDocument->createElement($sContainerName);
				if ($sMode !== NULL) {
					$elemParent->setAttribute('mode', $sMode);
				}
				$this->elemSubject->appendChild($elemParent);
			} else {
				$elemParent = $this->elemSubject;
			}
			
			if ($bReverse) {
				$niList->reverse();
			}
			foreach ($niList as $nodeCurrent) {
				$elemParent->appendChild($nodeCurrent->getElement());
			}
			if ($bReverse) {
				$niList->reverse();
			}
			
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM: 
	* @param 
	* @return 
	*/
	public function loadChildren($sMode = 'debug', $bStoreAsNodes = TRUE, $bReturnChildren = FALSE, $bLoadProperties = FALSE, $bOnlyReadable = FALSE) {
		
		// FIXME: IMPLEMENT DIFFERENT WAY OF CHECKING PRIMARY LINK!!!!!!!
		$niChildren = $this->getChildren($sMode, $bOnlyReadable);
		
		foreach ($niChildren as $nodeChild) {
			/*if ($nodeChild->getParent()->isSame($this)) {
				$sPrimary = 'TRUE';
			} else {
				$sPrimary = 'FALSE';
			}
			$nodeChild->setProperty('primary', $sPrimary);*/
			$iNumChildren = $nodeChild->getNumberOfChildren($sMode);
			// FIXME: setAttribute IS NOT AVAILABLE IN sbCR_Node!!!!!! only in sbNode
			$nodeChild->setAttribute('subnodes', $iNumChildren);
			if ($bLoadProperties) {
				$nodeChild->loadProperties();
			}
		}
		
		if ($bStoreAsNodes) {
			$this->aChildNodes[$sMode] = $niChildren;
		}
		
		if ($bReturnChildren) {
			return ($niChildren);
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* Makes the corresponding method of sbCR_Node public
	* @param 
	* @return 
	*/
	public function getChildren($sMode = 'debug', $bOnlyReadable = FALSE) {
		return (parent::getChildren($sMode, $bOnlyReadable));
	}
	
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM!!!
	* @param 
	* @return 
	*/
	public function getAncestorOfType($sNodetype) {
		try {
			$nodeParent = $this->getParent();
			if ($nodeParent->getPrimaryNodeType() == $sNodetype) {
				return ($nodeParent);
			} else {
				return ($nodeParent->getAncestorOfType($sNodetype));
			}
		} catch (Exception $e) {
			throw $e;
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM: 
	* @param 
	* @return 
	*/
	public function getElementTree($sMode) {
		
		if (!isset($this->aChildNodes[$sMode])) {
			throw new sbException('no children found for mode "'.$sMode.'"');
		}
		
		$elemSubject = $this->elemSubject->cloneNode();
		if (!$this->aChildNodes[$sMode]->isEmpty()) {
			foreach ($this->aChildNodes[$sMode] as $nodeChild) {
				$elemSubject->appendChild($nodeChild->getElementTree($sMode));
			}
		}
		return ($elemSubject);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM: 
	* @param 
	* @return 
	*/
	public function loadAncestors() {
		
		$niAncestors = $this->getAncestors();
		$this->niAncestors = $niAncestors;
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM: 
	* @param 
	* @return 
	*/
	public function loadParents() {
		try {
			$niParents = $this->getParents();
			$this->niParents = $niParents;
		} catch (ItemNotFoundException $e) {
			return (FALSE);
		}
	}
	
	
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM:
	* @param 
	* @return 
	*/
	public function isDescendantOf($nodeSubject) {
		return (parent::isDescendantOf($nodeSubject));
	}
	
	//--------------------------------------------------------------------------
	/**
	* CUSTOM:
	* @param 
	* @return 
	*/
	public function isAncestorOf($nodeSubject) {
		return (parent::isAncestorOf($nodeSubject));
	}
	
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------
	// views & actions
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function loadViews($bReturnViews = TRUE) {
		
		$crNodeType = $this->getSession()->getWorkspace()->getNodeTypeManager()->getNodeType($this->getPrimaryNodeType());
		$aViews = $crNodeType->getSupportedViews();
		
		if (User::isAuthorised('grant', $this)) {
			$aViews['security']['s_classfile'] = 'sb.node.view.security';
			$aViews['security']['s_class'] = 'sbView_security';
			$aViews['security']['s_module'] = 'sb_system';
			$aViews['security']['b_default'] = 'FALSE';
			$aViews['security']['b_display'] = 'TRUE';
		}
		
		// TODO: improve debug stuff
		if (Registry::getValue('sb.system.debug.tab.enabled') && User::isAdmin()) {
			$aViews['debug']['s_classfile'] = 'sb.node.view.debug';
			$aViews['debug']['s_class'] = 'sbView_debug';
			$aViews['debug']['s_module'] = 'sb_system';
			$aViews['debug']['b_default'] = 'TRUE';
			$aViews['debug']['b_display'] = 'TRUE';
		}
		
		$elemViews = $this->elemSubject->ownerDocument->createElement('views');
		foreach ($aViews as $sView => $aDetails) {
			$elemView = $this->elemSubject->ownerDocument->createElement('view');
			// TODO: find cleaner way to distinct non-display views
			if ($aDetails['b_display'] == 'TRUE') {
				$elemView->setAttribute('name', $sView);
				$elemView->setAttribute('module', $aDetails['s_module']);
				$elemViews->appendChild($elemView);
			}
		}
		
		$this->aViews = $aViews;
		
		if ($bReturnViews) {
			return($elemViews);
		} else {
			// TODO: save views in another way?
			$this->elemViews = $elemViews;
			$this->elemSubject->appendChild($elemViews);
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function getDefaultView() {
		foreach ($this->aViews as $sCurrentView => $aDetails) {
			$sView = $sCurrentView;
			if ($aDetails['b_default'] == 'TRUE') {
				$sView = $sCurrentView;
				break;
			}
		}
		return ($sView);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function getActionDetails($sView, $sAction = NULL) {
		
		if ($sView == 'debug' && Registry::getValue('sb.system.debug.tab.enabled')) {
			$aRow['s_classfile'] = 'sb.node.view.debug';
			$aRow['s_class'] = 'sbView_debug';
			$aRow['s_module'] = 'sb_system';
			$aRow['s_action'] = 'debug';
			$aRow['e_outputtype'] = 'rendered';
			$aRow['s_stylesheet'] = 'sb_system:node.debug.xsl';
			$aRow['s_mimetype'] = 'text/html';
			$aRow['b_uselocale'] = 'TRUE';
			return($aRow);
		}
		
		if (User::isAuthorised('grant', $this) && $sView == 'security') {
			$aActions = array(
				'display' => array(
					's_classfile' => 'sb.node.view.security',
					's_class' => 'sbView_security',
					's_module' => 'sb_system',
					's_action' => 'display',
					'e_outputtype' => 'rendered',
					's_stylesheet' => 'sb_system:node.security.xsl',
					's_mimetype' => 'text/html',
					'b_uselocale' => 'TRUE',
				),
				'changeInheritance' => array(
					's_classfile' => 'sb.node.view.security',
					's_class' => 'sbView_security',
					's_module' => 'sb_system',
					's_action' => 'changeInheritance',
					'e_outputtype' => 'rendered',
					's_stylesheet' => 'sb_system:node.security.xsl',
					's_mimetype' => 'text/html',
					'b_uselocale' => 'TRUE',
				),
				'editAuthorisations' => array(
					's_classfile' => 'sb.node.view.security',
					's_class' => 'sbView_security',
					's_module' => 'sb_system',
					's_action' => 'editAuthorisations',
					'e_outputtype' => 'rendered',
					's_stylesheet' => 'sb_system:node.security.editauthorisations.xsl',
					's_mimetype' => 'text/html',
					'b_uselocale' => 'TRUE',
				),
				'saveAuthorisations' => array(
					's_classfile' => 'sb.node.view.security',
					's_class' => 'sbView_security',
					's_module' => 'sb_system',
					's_action' => 'saveAuthorisations',
					'e_outputtype' => 'rendered',
					's_stylesheet' => 'sb_system:node.security.editauthorisations.xsl',
					's_mimetype' => 'text/html',
					'b_uselocale' => 'TRUE',
				),
				'addUser' => array(
					's_classfile' => 'sb.node.view.security',
					's_class' => 'sbView_security',
					's_module' => 'sb_system',
					's_action' => 'addUser',
					'e_outputtype' => 'rendered',
					's_stylesheet' => 'sb_system:node.security.xsl',
					's_mimetype' => 'text/html',
					'b_uselocale' => 'TRUE',
				),
				'removeUser' => array(
					's_classfile' => 'sb.node.view.security',
					's_class' => 'sbView_security',
					's_module' => 'sb_system',
					's_action' => 'removeUser',
					'e_outputtype' => 'rendered',
					's_stylesheet' => 'sb_system:node.security.xsl',
					's_mimetype' => 'text/html',
					'b_uselocale' => 'TRUE',
				),
			);
			if ($sAction == NULL) {
				return ($aActions['display']);	
			} elseif (isset($aActions[$sAction])) {
				return ($aActions[$sAction]);
			} else {
				return (FALSE);
			}
		}
		
		if ($sAction != NULL) {
			$stmtViews = $this->prepareKnown('actions/getDetails/given');
			$stmtViews->bindValue(':uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
			$stmtViews->bindValue(':view', $sView, PDO::PARAM_STR);
			$stmtViews->bindValue(':action', $sAction, PDO::PARAM_STR);
			$stmtViews->execute();
		} else {
			$stmtViews = $this->prepareKnown('actions/getDetails/default');
			$stmtViews->bindValue(':uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
			$stmtViews->bindValue(':view', $sView, PDO::PARAM_STR);
			$stmtViews->execute();
		}
		
		$aViews = $stmtViews->fetchAll(PDO::FETCH_ASSOC);
		$stmtViews->closeCursor();
		
		foreach ($aViews as $aRow) {
			return ($aRow);
		}
		
		return(FALSE);
		
	}
	
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function callView($sView = NULL, $sAction = NULL) {
		
		global $_RESPONSE;
		
		$this->loadViews(FALSE);
		
		if ($sView == NULL) {
			$sView = $this->getDefaultView();
		}
		
		if (isset($this->aViews[$sView])) {
			
			$aAction = $this->getActionDetails($sView, $sAction);
			
			if ($aAction !== FALSE) {
				
				// process view & action info
				$sClassfile = $this->aViews[$sView]['s_classfile'];
				$sClass = $this->aViews[$sView]['s_class'];
				$sModule = $this->aViews[$sView]['s_module'];
				$sAction = $aAction['s_action'];
				if ($aAction['b_uselocale'] == 'TRUE') {
					$bUseLocale = TRUE;
				} else {
					$bUseLocale = FALSE;
				}
				if ($aAction['s_classfile'] != NULL) {
					$sClassfile = $aAction['s_classfile'];
					$sClass = $aAction['s_class'];
					$sModule = $aAction['s_module'];
				}
				
				// init module
				import($sModule.':init', FALSE);
				
				// import class file and create instance
				import($sModule.':'.$sClassfile);
				
				if (!class_exists($sClass)) {
					throw new sbException('Class does not exist: '.$sClass);
				}
				$viewCurrent = new $sClass($this);
				
				// check if login is necessary
				if ($viewCurrent->requiresLogin() && !User::isLoggedIn()) {
					//$nodeRoot = $this->getSession()->getRootNode();
					//return($this->redirect($nodeRoot->getProperty('jcr:uuid'), 'login'));
					$_RESPONSE->redirect('-', 'login');
				}
				
				if (!$_RESPONSE->hasRequestData()) {
					$_RESPONSE->addRequestData($this->getProperty('jcr:uuid'), $sView, $sAction);
				}
				
				// execute action and store data
				$elemView = $viewCurrent->execute($sAction);
				
				$_RESPONSE->setRenderMode($aAction['e_outputtype'], $aAction['s_mimetype'], $aAction['s_stylesheet']);
				$_RESPONSE->setLocaleMode($bUseLocale);
				if ($bUseLocale) {
					$_RESPONSE->addLocale($sModule);
					$_RESPONSE->addLocale($this->getModule());
				}
				
				return ($elemView);
				
			} else {
				throw new ActionUndefinedException('"'.$sAction.'" for "'.$sView.'" in node '.$this->getProperty('jcr:uuid').' ('.$this->getPrimaryNodeType().')');
			}
			
		} else {
			throw new ViewUndefinedException('"'.$sView.'" in node '.$this->getProperty('jcr:uuid').' ('.$this->getPrimaryNodeType().')');
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getViews() {
		return ($this->elemViews);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function storeSupertypeNames() {
		$crNodeType = $this->getNodeType();
		$elemContainer = $this->elemSubject->ownerDocument->createElement('supertypes');
		foreach ($crNodeType->getSupertypeNames() as $sSupertype) {
			$elemSupertype = $this->elemSubject->ownerDocument->createElement('nodetype');
			$elemSupertype->setAttribute('name', $sSupertype);
			$elemContainer->appendChild($elemSupertype);
		}
		$this->elemSubject->appendChild($elemContainer);
	}
	
	
	
	
	
	//--------------------------------------------------------------------------
	/**
	* Returns a sbCR_NodeIterator with all nodes that contain references to this
	* node. If there are no referencing nodes the iterator will be empty.
	* @return sbCR_NodeIterator 
	*/
	public function getReferencingNodes() {
		
		$stmtGetReferences = $this->crSession->prepareKnown('sbCR/node/getReferences');
		$stmtGetReferences->bindValue('uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetReferences->execute();
		$aReferences = $stmtGetReferences->fetchAll();
		
		$aReferencingNodes = array();
		foreach ($aReferences as $aRow) {
			$nodeCurrent = $this->crSession->getNode($aRow['fk_node']);
			$aReferencingNodes[] = $nodeCurrent;
		}
		
		$niReferencingNodes = new sbCR_NodeIterator($aReferencingNodes);
		
		return ($niReferencingNodes);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* Returns a sbCR_NodeIterator with all nodes containing a softlink to this 
	* node. If there are no softlinks the iterator will be empty.
	* @return sbCR_NodeIterator
	*/
	public function getWeakReferencingNodes() {
		
		$stmtGetSoftlinks = $this->crSession->prepareKnown('sbCR/node/getSoftlinks');
		$stmtGetSoftlinks->bindValue('uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetSoftlinks->execute();
		$aSoftlinks = $stmtGetSoftlinks->fetchAll();
		
		$aLinkingNodes = array();
		foreach ($aSoftlinks as $aRow) {
			$nodeCurrent = $this->crSession->getNode($aRow['fk_node']);
			$aLinkingNodes[] = $nodeCurrent;
		}
		
		$niLinkingNodes = new sbCR_NodeIterator($aLinkingNodes);
		
		return ($niLinkingNodes);
		
	}
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------
	// custom sbCR stuff
	//--------------------------------------------------------------------------
	/**
	* TODO: check if this is necessary
	* @param 
	* @return 
	*/
	private function redirect($iNodeID, $sView = NULL, $sAction = NULL) {
		$nodeCurrent = $this->crSession->getNode($iNodeID);
		$elemViews = $nodeCurrent->loadViews(TRUE);
		$elemData = $nodeCurrent->callView($sView, $sAction);
		return ($elemData);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getContextMenu($sParentUUID) {
		
		// basic data
		$elemContextMenu = ResponseFactory::createElement('contextmenu');
		//$elemContextMenu->setAttribute('new', 'TRUE');
		$elemContextMenu->setAttribute('uuid', $this->getProperty('jcr:uuid'));
		$elemContextMenu->setAttribute('parent', $sParentUUID);
		$elemContextMenu->setAttribute('refresh', 'TRUE');
		
		// clipboard data
		if (isset(sbSession::$aData['clipboard'])) {
			$nodeSubject = $this->crSession->getNodeByIdentifier(sbSession::$aData['clipboard']['childnode']);
			// TODO: also check for parent(s)!
			if (!$nodeSubject->isAncestorOf($this) && !$nodeSubject->isSame($this)) {
				$elemContextMenu->setAttribute('clipboard', 'TRUE');
				$elemContextMenu->setAttribute('clipboard_type', sbSession::$aData['clipboard']['type']);
				$elemContextMenu->setAttribute('clipboard_subject', $nodeSubject->getProperty('label'));
			}
		}
		
		// TODO: find another, more versatile solution for this
		$sDeletable = 'FALSE';
		if ($this->getProperty('sbcr:isDeletable')) {
			$sDeletable = 'TRUE';
		}
		$elemContextMenu->setAttribute('delete', $sDeletable);
		
		$sMode = 'create';
		$stmtGetAllowedSubtypes = $this->crSession->prepareKnown('sbSystem/node/getAllowedSubtypes');
		$stmtGetAllowedSubtypes->bindValue('parent_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetAllowedSubtypes->bindValue('mode', $sMode, PDO::PARAM_STR);
		$stmtGetAllowedSubtypes->execute();
		
		foreach ($stmtGetAllowedSubtypes as $aRow) {
			$elemNew = ResponseFactory::createElement('new');
			$elemNew->setAttribute('nodetype', $aRow['fk_nodetype']);
			$elemNew->setAttribute('csstype', $aRow['s_csstype']);
			$elemContextMenu->appendChild($elemNew);
			$sModule = substr($aRow['fk_nodetype'], 0, strpos($aRow['fk_nodetype'], ':'));
			global $_RESPONSE;
			$_RESPONSE->addLocale($sModule);
		}
		
		$stmtGetAllowedSubtypes->closeCursor();
		
		return ($elemContextMenu);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	/*public function getSubject() {
		return ($this->elemSubject);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function buildForm($sMode = 'properties', $sParentUUID = '') {
		
		global $_RESPONSE;
		$this->initPropertyDefinitions();
		
		switch ($sMode) {
			
			case 'properties':
				if (method_exists($this, 'buildPropertiesForm')) {
					return ($this->buildPropertiesForm());
				} else {
					
					$formProperties = new sbDOMForm(
						'properties',
						'$locale/system/general/labels/properties',
						'/'.$this->getProperty('jcr:uuid').'/properties/save',
						$this->crSession
					);
					foreach ($this->crPropertyDefinitionCache as $sName => $aDetails) {
						if ($aDetails['b_showinproperties'] == 'TRUE') {
							$formProperties->addInput($sName.';'.$aDetails['s_internaltype'], $aDetails['s_labelpath']);
							try {
								$formProperties->setValue($sName, $this->getProperty($sName));
							} catch (PathNotFoundException $e) {
								// ignore
							}
							if ($aDetails['b_protected'] == 'TRUE') {
								$formProperties->disable($sName);
							}
						}
					}
					$formProperties->addSubmit('$locale/system/general/actions/save');
					$this->modifyForm($formProperties, 'properties');
					return ($formProperties);
					
				}
				
			case 'create':
				
				$_RESPONSE->addLocale($this->getModule());
				
				if (method_exists($this, 'buildCreateForm')) {
					return ($this->buildCreateForm($sParentUUID));
				} else {
					
					$formCreate = new sbDOMForm(
						'create',
						'$locale/system/general/actions/create',
						'/-/structure/saveChild/nodetype='.$this->getProperty('nodetype').'&parentnode='.$sParentUUID,
						$this->crSession
					);
					foreach ($this->crPropertyDefinitionCache as $sName => $aDetails) {
						if ($aDetails['b_showinproperties'] == 'TRUE') {
							$formCreate->addInput($sName.';'.$aDetails['s_internaltype'], $aDetails['s_labelpath']);
							//$formCurrent->setValue($sName, $this->getProperty($aDetails['s_attributename']));
						}
					}
					$_RESPONSE->addSystemMeta('csstype', $this->getProperty('csstype'));
					$formCreate->addSubmit('$locale/system/general/actions/save');
					$this->modifyForm($formCreate, 'create');
					return ($formCreate);
					
				}
				
				
			
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function modifyForm($formCurrent, $sMode) { }
	
	//--------------------------------------------------------------------------
	/**
	* Wraps the sbCR_Node method to return NULL instead of throwing an 
	* exception.
	* @param 
	* @return NULL if property does not exist, otherwise property value 
	*/
	public function getProperty($sName) {
		try {
			$mValue = parent::getProperty($sName);
			return ($mValue);
		} catch (PathNotFoundException $e) {
			return (NULL);
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function setAttribute($sName, $mValue) {
		
		$this->initPropertyDefinitions();
		if ($this->crPropertyDefinitionCache->hasProperty($sName)) {
			parent::setProperty($sName, $mValue);
		} else {
			$this->elemSubject->setAttribute($sName, $mValue);
		}
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getNumberOfChildren($sMode = NULL) {
		
		if ($this->elemSubject->getAttribute('query') == 'new') {
			throw new RepositoryException('new nodes don\'t have children');
		} else {
			if ($sMode != NULL && $sMode != 'debug') {
				$stmtGetChildren = $this->crSession->prepareKnown($this->aQueries['countChildren']['byMode']);
				$stmtGetChildren->bindValue('parent_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
				$stmtGetChildren->bindValue('mode', $sMode, PDO::PARAM_STR);
				$stmtGetChildren->execute();
			} else {
				$stmtGetChildren = $this->crSession->prepareKnown($this->aQueries['countChildren']['debug']);
				$stmtGetChildren->bindValue('parent_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
				$stmtGetChildren->execute();
			}
			$aResults = $stmtGetChildren->fetchAll(PDO::FETCH_ASSOC);
			$stmtGetChildren->closeCursor();
			$iNumberOfChildren = $aResults[0]['num_children'];
			return ($iNumberOfChildren);
			
		}
		
	}
	
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function moveToPosition($nodeNewParent, $nodeOldParent, $iPosition) {
		
		
		
		
		
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getStylesheet($sMode = NULL) {
		$sStylesheet = '<?xml version="1.0" encoding="UTF-8"?>
			<xsl:stylesheet 
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
				version="1.0" 
				exclude-result-prefixes="html" 
				xmlns:html="http://www.w3.org/1999/xhtml"
			>
			
			<xsl:template match="/response">
				<em>This node ('.$this->getPrimaryNodeType().') cannot deliver a stylesheet for the required mode ('.$sMode.')</em>
			</xsl:template>

			</xsl:stylesheet>';
		$domStylesheet = new sbDOMDocument();
		$domStylesheet->loadXML($sStylesheet);
		return ($domStylesheet);
	}
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------
	// voting
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function placeVote($sUserUUID = NULL, $iVote) {
		if ($sUserUUID == NULL) {
			throw new sbException('voting needs user uuid');	
		}
		$stmtPlaceVote = $this->prepareKnown('voting/placeVote');
		$stmtPlaceVote->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtPlaceVote->bindValue(':user_uuid', $sUserUUID, PDO::PARAM_STR);
		$stmtPlaceVote->bindValue(':vote', $iVote, PDO::PARAM_INT);
		$stmtPlaceVote->execute();
		$this->refreshGlobalVote();
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function removeVote($sUserUUID = NULL) {
		if ($sUserUUID == NULL) {
			throw new sbException('voting needs user uuid');	
		}
		$stmtPlaceVote = $this->prepareKnown('voting/removeVote');
		$stmtPlaceVote->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtPlaceVote->bindValue(':user_uuid', $sUserUUID, PDO::PARAM_STR);
		$stmtPlaceVote->execute();
		$this->refreshGlobalVote();
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getVote($sUserUUID = NULL) {
		if ($sUserUUID == NULL) {
			$nodeAll = $this->crSession->getRootNode();
			$sUserUUID = $nodeAll->getProperty('jcr:uuid');
		}
		$stmtGetVote = $this->prepareKnown('voting/getUserVote');
		$stmtGetVote->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetVote->bindValue(':user_uuid', $sUserUUID, PDO::PARAM_STR);
		$stmtGetVote->execute();
		foreach ($stmtGetVote as $aRow) {
			$this->setAttribute('vote', $aRow['n_vote']);
			return ($aRow['n_vote']);
		}
		return (NULL);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getVotes() {
		$stmtGetVotes = $this->prepareKnown('voting/getAllVotes');
		$stmtGetVotes->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetVotes->execute();
		$aVotes = array();
		foreach ($stmtGetVotes as $aRow) {
			$aVotes[] = $aRow;
		}
		return ($aVotes);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function refreshGlobalVote() {
		$nodeAll = $this->crSession->getRootNode();
		$stmtGetVotes = $this->prepareKnown('voting/getAverageVote');
		$stmtGetVotes->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetVotes->bindValue(':ignore_uuid', $nodeAll->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetVotes->execute();
		foreach ($stmtGetVotes as $aRow) {
			$stmtPlaceVote = $this->prepareKnown('voting/placeVote');
			$stmtPlaceVote->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
			$stmtPlaceVote->bindValue(':user_uuid', $nodeAll->getProperty('jcr:uuid'), PDO::PARAM_STR);
			$stmtPlaceVote->bindValue(':vote', round($aRow['n_average']), PDO::PARAM_INT);
			$stmtPlaceVote->execute();
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------
	// tags
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function initTags() {
		if (is_array($this->aTags)) {
			return (FALSE);
		}
		$this->aTags = array();
		if (!$this->isNew()) {
			$stmtGetTags = $this->prepareKnown('tagging/getAllNodeTags');
			$stmtGetTags->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
			$stmtGetTags->execute();
			foreach ($stmtGetTags as $aRow) {
				$this->aTags[$aRow['s_tag']] = $aRow['id'];
			}
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function hasTag($sTag) {
		$this->initTags();
		if (isset($this->aTags[$sTag]) || isset($this->aNewTags[$sTag])) {
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
	public function addTag($sTag) {
		$this->initTags();
		if (!$this->hasTag($sTag)) {
			$this->aNewTags[$sTag] = TRUE;
			$this->addSaveTask('add_tag', array('tag' => $sTag));
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function addTags($aTags) {
		foreach ($aTags as $sTag) {
			$this->addTag($sTag);
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	/*public function setTags($aTags) {
		foreach ($aTags as $sTag) {
			$this->addTag($sTag);	
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function removeTag($sTag) {
		$this->initTags();
		if (!$this->hasTag($sTag)) {
			throw new sbException('tag "'.$sTag.'" is not assigned to this node');	
		} else {
			$this->addSaveTask('remove_tag', array('tag' => $sTag));
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getTags() {
		$this->initTags();
		$aCurrentTags = array();
		foreach ($this->aTags as $sTag => $unused) {
			$aCurrentTags[] = $sTag;
		}
		foreach ($this->aNewTags as $sTag => $unused) {
			$aCurrentTags[] = $sTag;
		}
		return ($aCurrentTags);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getBranchTags() {
		if ($this->aBranchTags !== NULL) {
			return ($this->aBranchTags);
		}
		$stmtGetTags = $this->prepareKnown('tagging/getAllBranchTags');
		$stmtGetTags->bindValue('root_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetTags->execute();
		foreach ($stmtGetTags as $aRow) {
			$this->aBranchTags[$aRow['id']] = array(
				'tag' => $aRow['s_tag'],
				'numitems' => $aRow['n_numitemstagged'],
				'popularity' => $aRow['n_popularity'],
				'customweight' => $aRow['n_customweight'],
			);
		}
		return ($this->aBranchTags);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function getTagID($sTag) {
		$this->initTags();
		if (isset($this->aTags[$sTag])) {
			return ($this->aTags[$sTag]);
		}
		$stmtGetID = $this->prepareKnown('tagging/getTagID');
		$stmtGetID->bindValue('tag', $sTag, PDO::PARAM_STR);
		$stmtGetID->execute();
		foreach ($stmtGetID as $aRow) {
			return ($aRow['id']);
		}
		return (FALSE);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function createNewTag($sTag) {
		$stmtNew = $this->prepareKnown('tagging/createNewTag');
		$stmtNew->bindValue('tag', $sTag, PDO::PARAM_STR);
		$stmtNew->execute();
		return ($this->crSession->lastInsertId());
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function getTag($iTagID) {
		$stmtGetID = $this->prepareKnown('tagging/getTag');
		$stmtGetID->bindValue('tag_id', $iTagID, PDO::PARAM_INT);
		$stmtGetID->execute();
		foreach ($stmtGetID as $aRow) {
			return ($aRow['s_tag']);
		}
		return (FALSE);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function increaseTagPopularity($iTagID) {
		$stmtIncPop = $this->prepareKnown('tagging/increasePopularity');
		$stmtIncPop->bindValue('tag_id', $iTagID, PDO::PARAM_INT);
		$stmtIncPop->execute();
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------
	// comments
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	/**
	* 
	* @param 
	* @return 
	*/
	/*public function placeComment($sUserUUID = NULL, $sComment) {
		if ($sUserUUID == NULL) {
			throw new sbException('voting needs user uuid');	
		}
		$stmtPlaceVote = $this->prepareKnown($this->aQueries['voting']['placeVote']);
		$stmtPlaceVote->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtPlaceVote->bindValue(':user_uuid', $sUserUUID, PDO::PARAM_STR);
		$stmtPlaceVote->bindValue(':vote', $iVote, PDO::PARAM_INT);
		$stmtPlaceVote->execute();
		$this->refreshGlobalVote();
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	/*public function removeVote($sUserUUID = NULL) {
		if ($sUserUUID == NULL) {
			throw new sbException('voting needs user uuid');	
		}
		$stmtPlaceVote = $this->prepareKnown($this->aQueries['voting']['removeVote']);
		$stmtPlaceVote->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtPlaceVote->bindValue(':user_uuid', $sUserUUID, PDO::PARAM_STR);
		$stmtPlaceVote->execute();
		$this->refreshGlobalVote();
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	/*public function getVote($sUserUUID = NULL) {
		if ($sUserUUID == NULL) {
			$nodeAll = $this->crSession->getRootNode();
			$sUserUUID = $nodeAll->getProperty('jcr:uuid');
		}
		$stmtGetVote = $this->prepareKnown($this->aQueries['voting']['getVote']['byUser']);
		$stmtGetVote->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetVote->bindValue(':user_uuid', $sUserUUID, PDO::PARAM_STR);
		$stmtGetVote->execute();
		foreach ($stmtGetVote as $aRow) {
			$this->setAttribute('vote', $aRow['n_vote']);
			return ($aRow['n_vote']);
		}
		return (NULL);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	/*protected function refreshGlobalVote() {
		$nodeAll = $this->crSession->getRootNode();
		$stmtGetVotes = $this->prepareKnown($this->aQueries['voting']['getVote']['average']);
		$stmtGetVotes->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetVotes->bindValue(':ignore_uuid', $nodeAll->getProperty('jcr:uuid'), PDO::PARAM_STR);
		$stmtGetVotes->execute();
		foreach ($stmtGetVotes as $aRow) {
			$stmtPlaceVote = $this->prepareKnown($this->aQueries['voting']['placeVote']);
			$stmtPlaceVote->bindValue(':subject_uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_STR);
			$stmtPlaceVote->bindValue(':user_uuid', $nodeAll->getProperty('jcr:uuid'), PDO::PARAM_STR);
			$stmtPlaceVote->bindValue(':vote', round($aRow['n_average']), PDO::PARAM_INT);
			$stmtPlaceVote->execute();
		}
	}
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------
	// authorisations
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	/*public function loadEffectiveAuthorisations() {
		$this->loadSupportedAuthorisations();
		$aInherited = $this->loadInheritedAuthorisations(TRUE);
		$aLocal = $this->loadLocalAuthorisations(TRUE);
		$aMerged = merge_authorisations($aLocal, $aInherited);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function isAuthorised($sAuthorisation, $sEntityID = NULL) {
		// admin is allowed everything
		if (User::isAdmin()) {
			return (TRUE);
		}
		
		// load authorisations if not cached
		if (!isset($this->aAggregatedAuthorisations[$sEntityID])) {
			$this->loadUserAuthorisations();
		}
		
		// check authorisation
		if (isset($this->aAggregatedAuthorisations[$sEntityID][$sAuthorisation])) {
			if ($this->aAggregatedAuthorisations[$sEntityID][$sAuthorisation] == 'ALLOW') {
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
	public function loadUserAuthorisations($bSaveToElement = TRUE) {
		
		$aAuthorisations = array();
		if (User::isLoggedIn()) {
			$sUserUUID = User::getUUID();
		} else {
			$sUserUUID = 'sb_system:guests';
		}
		
		// check cache 
		if (Registry::getValue('sb.system.cache.authorisations.enabled')) {
			$cacheAuth = CacheFactory::getInstance('authorisations');
			$aAuthorisations = $cacheAuth->loadAuthorisations($this->getProperty('jcr:uuid'), $sUserUUID, AuthorisationCache::AUTH_EFFECTIVE);
		}
		
		if (count($aAuthorisations) == 0) {
			import('sb.system.authorisation');
			$this->aggregateAuthorisations(User::getUUID());
			merge_auth_local_supported($this->aAggregateAuthorisations[$sUserUUID], $this->aSupportedAuthorisations);
			$aAuthorisations = $this->aAggregateAuthorisations[$sUserUUID];
			// write cache
			if (Registry::getValue('sb.system.cache.authorisations.enabled')) {
				$chacheAuth = CacheFactory::getInstance('authorisations');
				$cacheAuth->storeAuthorisations($this->getProperty('jcr:uuid'), $sUserUUID, AuthorisationCache::AUTH_EFFECTIVE, $this->aAggregateAuthorisations[$sUserUUID]);
			}
		}
		
		// apply rights hierarchy
		//echo $this->getProperty('name').' ######################################<br>';
		//echo '------------------------------------- before: ';
		//var_dumpp($this->aAggregateAuthorisations);
		
		//echo '------------------------------------- after: ';
		//var_dumpp($this->aAggregateAuthorisations);
		
		// store cache
		
		
		if ($bSaveToElement) {
			$elemContainer = ResponseFactory::createElement('user_authorisations');
			foreach ($aAuthorisations as $sAuthorisation => $sGrantType) {
				$elemAuthorisation = $this->elemSubject->ownerDocument->createElement('authorisation');
				$elemAuthorisation->setAttribute('name', $sAuthorisation);
				$elemAuthorisation->setAttribute('grant_type', $sGrantType);
				$elemContainer->appendChild($elemAuthorisation);
			}
			$this->elemSubject->appendChild($elemContainer);
		}
		
		
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function aggregateAuthorisations($sEntityUUID) {
		
		if ($this->aSupportedAuthorisations == NULL) {
			$this->loadSupportedAuthorisations();
		}
		
		// check cache
		
		$aLocalAuth = array();
		$nodeEntity = $this->crSession->getNode($sEntityUUID);
		
		// get effective local authorisations
		if ($nodeEntity->getPrimaryNodetype() == 'sb_system:user') {
			$aGroupAuth = array();
			$niGroups = $nodeEntity->getParentsByNodetype('sb_system:usergroup');
			foreach ($niGroups as $nodeGroup) {
				merge_auth_groups($aGroupAuth, $this->aggregateAuthorisations($nodeGroup->getProperty('jcr:uuid')));
			}
			$aUserAuth = $this->getLocalEntityAuthorisations($sEntityUUID);
			merge_auth_user_group($aUserAuth, $aGroupAuth);
			$aLocalAuth = $aUserAuth;
		} else {
			$aLocalAuth = $this->getLocalEntityAuthorisations($sEntityUUID);
		}
		
		// merge with parent authorisations
		if ($this->getProperty('inheritrights') == 'TRUE') {
			try {
				$nodeParent = $this->getParent();
				if ($nodeParent->getProperty('bequeathrights') == 'TRUE') {
					$aParentAuth = $nodeParent->aggregateAuthorisations($sEntityUUID);
					$aLocalAuth = merge_auth_local_parent($aLocalAuth, $aParentAuth);
				}
			} catch (ItemNotFoundException $e) {
				// ignore
			}
		}
		
		
		
		// write cache 
		
		$this->aAggregateAuthorisations[$sEntityUUID] = $aLocalAuth;
		return ($aLocalAuth);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	protected function getLocalEntityAuthorisations($sEntityUUID) {
		$sNodeUUID = $this->getProperty('jcr:uuid');
		$stmtAuthorisations = $this->prepareKnown($this->aQueries['loadLocalEntityAuthorisations']);
		$stmtAuthorisations->bindParam('node_uuid', $sNodeUUID, PDO::PARAM_STR);
		$stmtAuthorisations->bindParam('entity_uuid', $sEntityUUID, PDO::PARAM_STR);
		$stmtAuthorisations->execute();
		$aAuthorisations = array();
		foreach ($stmtAuthorisations as $aRow) {
			$aAuthorisations[$aRow['fk_authorisation']]	= $aRow['e_granttype'];
		}
		return ($aAuthorisations);
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function loadSecurityAuthorisations() {
		
		global $_RESPONSE;
		
		$this->storeSupportedAuthorisations();
		$this->loadInheritedAuthorisations();
		$this->loadLocalAuthorisations();
		
		$nodeUseraccounts = $this->crSession->getNode('//*[@uid="sb_system:useraccounts"]');
		// FIXME: loading these destroys response!!?!?
		$aResultNodes['users'] = $nodeUseraccounts->callView('gatherdata', 'users', NULL, $_RESPONSE);
		$aResultNodes['groups'] = $nodeUseraccounts->callView('gatherdata', 'groups', NULL, $_RESPONSE);
		
		return ($aResultNodes);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	// TODO: implement 2 variants
	public function loadInheritedAuthorisations($bReturnAsArray = FALSE, $bSaveToElement = TRUE) {
				
		import('sb.system.authorisation');
		$aMerged = array();
		$this->loadProperties('extended');
		
		if ($this->getProperty('inheritrights') == 'TRUE') {
			$_CACHE = CacheFactory::getInstance('system');
			if ($_CACHE->exists('authorisations:array/'.$this->getProperty('jcr:uuid'))) {
				$aMerged = $_CACHE->loadData('authorisations:array/'.$this->getProperty('jcr:uuid'));
			} else {
				try {
					$nodeParent = $this->getParent();
					$aLocal = $nodeParent->loadLocalAuthorisations(TRUE, FALSE);
					//var_dump($aLocal);
					//var_dump($aInherited);
					if ($nodeParent->getProperty('bequeathrights') == 'TRUE') {
						$aInherited = $nodeParent->loadInheritedAuthorisations(TRUE, FALSE);
						$aMerged = merge_authorisations($aLocal, $aInherited);
						//$_CACHE->storeData('authorisations:array/'.$this->elemSubject->getAttribute('uuid'), $aMerged);
					}
				} catch (Exception $e) {
					
				}
			}
		}
		
		if ($bSaveToElement) {
			$elemContainer = ResponseFactory::createElement('inherited_authorisations');
			foreach ($aMerged as $iID => $aAuthorisations) {
				foreach ($aAuthorisations as $sAuthorisation => $sGrantType) {
					$elemAuthorisation = $this->elemSubject->ownerDocument->createElement('authorisation');
					//$elemAuthorisation->setAttribute('nodetype', $aRow['fk_userentitytype']);
					$elemAuthorisation->setAttribute('uuid', $iID);
					$elemAuthorisation->setAttribute('name', $sAuthorisation);
					$elemAuthorisation->setAttribute('grant_type', $sGrantType);
					$elemContainer->appendChild($elemAuthorisation);
				}
			}
			$this->elemSubject->appendChild($elemContainer);
		}
		
		if ($bReturnAsArray) {
			return ($aMerged);
		}
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function loadLocalAuthorisations($bReturnAsArray = FALSE, $bSaveToElement = TRUE) {
		
		$stmtAuthorisations = $this->prepareKnown($this->aQueries['loadLocalAuthorisations']);
		$stmtAuthorisations->bindValue(':uuid', $this->getProperty('jcr:uuid'), PDO::PARAM_INT);
		$stmtAuthorisations->execute();
		
		if ($bSaveToElement) {
			$elemContainer = $this->elemSubject->ownerDocument->createElement('local_authorisations');
			foreach ($stmtAuthorisations as $aRow) {
				$elemAuthorisation = $this->elemSubject->ownerDocument->createElement('authorisation');
				$elemAuthorisation->setAttribute('nodetype', $aRow['fk_userentitytype']);
				$elemAuthorisation->setAttribute('uuid', $aRow['fk_userentity']);
				$elemAuthorisation->setAttribute('name', $aRow['fk_authorisation']);
				$elemAuthorisation->setAttribute('grant_type', $aRow['e_granttype']);
				$elemContainer->appendChild($elemAuthorisation);
			}
			$this->elemSubject->appendChild($elemContainer);
		}
		
		if ($bReturnAsArray) {
			$aAuthorisations = array();
			foreach ($stmtAuthorisations as $aRow) {
				$aAuthorisations[$aRow['fk_userentity']][$aRow['fk_authorisation']] = $aRow['e_granttype'];
			}
			$stmtAuthorisations->closeCursor();
			return ($aAuthorisations);
		}
		$stmtAuthorisations->closeCursor();
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function loadSupportedAuthorisations() {
		
		if ($this->aSupportedAuthorisations == NULL) {
			$crNodeTypeManager = $this->crSession->getWorkspace()->getNodeTypeManager();
			$crNodeType = $crNodeTypeManager->getNodeType($this->getPrimaryNodeType());
			$aAuthorisations = $crNodeType->getSupportedAuthorisations();
			$this->aSupportedAuthorisations = $aAuthorisations;
		} else {
			$aAuthorisations = $this->aSupportedAuthorisations;
		}
		
		return ($this->aSupportedAuthorisations);
		
	}
	
	//--------------------------------------------------------------------------
	/**
	* 
	* @param 
	* @return 
	*/
	public function storeSupportedAuthorisations() {
		$elemContainer = $this->elemSubject->ownerDocument->createElement('supported_authorisations');
		foreach ($this->loadSupportedAuthorisations() as $sAuthorisation => $sParentAuthorisation) {
			$elemAuthorisation = $this->elemSubject->ownerDocument->createElement('authorisation');
			$elemAuthorisation->setAttribute('name', $sAuthorisation);
			if ($sParentAuthorisation != NULL) {
				$elemAuthorisation->setAttribute('parent', $sParentAuthorisation);
			} else {
				$elemAuthorisation->setAttribute('parent', '');
			}
			$elemContainer->appendChild($elemAuthorisation);
		}
		$this->elemSubject->appendChild($elemContainer);	
	}
	
	
}

?>