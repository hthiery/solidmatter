<?php

global $_QUERIES;

$_QUERIES['MAPPING']['{TABLE_SESSIONS}']	= '{PREFIX_SYSTEM}_system_sessions';
$_QUERIES['MAPPING']['{TABLE_FLATCACHE}']	= '{PREFIX_SYSTEM}_system_cache_flat';

$_QUERIES['MAPPING']['{TABLE_IMAGECACHE}']	= '{PREFIX_WORKSPACE}_system_cache_images';
$_QUERIES['MAPPING']['{TABLE_PROGRESS}']	= '{PREFIX_WORKSPACE}_system_progress';
$_QUERIES['MAPPING']['{TABLE_EVENTLOG}']	= '{PREFIX_WORKSPACE}_system_eventlog';
$_QUERIES['MAPPING']['{TABLE_MIMETYPES}']	= '{PREFIX_FRAMEWORK}_nodetypes_mimetypemapping';
$_QUERIES['MAPPING']['{TABLE_MODULES}']		= '{PREFIX_FRAMEWORK}_modules';

$_QUERIES['MAPPING']['{TABLE_USERS}']		= '{PREFIX_WORKSPACE}_system_useraccounts';
$_QUERIES['MAPPING']['{TABLE_REGISTRY}']	= '{PREFIX_WORKSPACE}_system_registry';

$_QUERIES['MAPPING']['{TABLE_VOTES}']		= '{PREFIX_WORKSPACE}_system_nodes_votes';
$_QUERIES['MAPPING']['{TABLE_NODETAGS}']	= '{PREFIX_WORKSPACE}_system_nodes_tags';
$_QUERIES['MAPPING']['{TABLE_TAGS}']		= '{PREFIX_WORKSPACE}_system_tags';

//------------------------------------------------------------------------------
// session
//------------------------------------------------------------------------------

$_QUERIES['sbSystem/session/load'] = '
	SELECT		s_data
	FROM		{TABLE_SESSIONS}
	WHERE		s_sessionid = :session_id
';
$_QUERIES['sbSystem/session/store'] = '
	INSERT INTO {TABLE_SESSIONS}
				(
					s_sessionid,
					s_data
				) VALUES (
					:session_id,
					:data
				)
	ON DUPLICATE KEY UPDATE
				s_data = :data
';
$_QUERIES['sbSystem/session/destroy'] = '
	DELETE FROM	{TABLE_SESSIONS}
	WHERE		s_sessionid = :session_id
';

//------------------------------------------------------------------------------
// ImageCache
//------------------------------------------------------------------------------

$_QUERIES['sb_system/cache/images/store'] = '
	INSERT INTO	{TABLE_IMAGECACHE}
				(
					fk_image,
					fk_filterstack,
					e_mode,
					m_content
				) VALUES (
					:image,
					:filterstack,
					:mode,
					:content
				)
';
$_QUERIES['sb_system/cache/images/load'] = '
	SELECT		m_content
	FROM		{TABLE_IMAGECACHE}
	WHERE		fk_image = :image
		AND		fk_filterstack = :filterstack
		AND		e_mode = :mode
';
$_QUERIES['sb_system/cache/images/clear/byImage'] = '
	DELETE FROM	{TABLE_IMAGECACHE}
	WHERE		fk_image = :filterstack
';
$_QUERIES['sb_system/cache/images/clear/byFilterstack'] = '
	DELETE FROM	{TABLE_IMAGECACHE}
	WHERE		fk_filterstack = :filterstack
';
$_QUERIES['sb_system/cache/images/empty'] = '
	DELETE FROM {TABLE_IMAGECACHE}
';

//------------------------------------------------------------------------------
// Flat Cache
//------------------------------------------------------------------------------

$_QUERIES['sbSystem/cache/flat/store'] = '
	INSERT INTO	{TABLE_FLATCACHE}
				(
					s_key,
					t_value
				) VALUES (
					:key,
					:data
				)
	ON DUPLICATE KEY UPDATE
				t_value = :data
';
$_QUERIES['sbSystem/cache/flat/load'] = '
	SELECT		t_value
	FROM		{TABLE_FLATCACHE}
	WHERE		s_key = :key
';
$_QUERIES['sbSystem/cache/flat/check'] = '
	SELECT		s_key
	FROM		{TABLE_FLATCACHE}
	WHERE		s_key = :key
';
$_QUERIES['sbSystem/cache/flat/clear'] = '
	DELETE FROM	{TABLE_FLATCACHE}
	WHERE		s_key LIKE :key
';
/*$_QUERIES['sbSystem/cache/flat/empty'] = '
	TRUNCATE TABLE {TABLE_FLATCACHE}
';*/

//------------------------------------------------------------------------------
// Progress
//------------------------------------------------------------------------------

$_QUERIES['sb_system/progress/update'] = '
	INSERT INTO	{TABLE_PROGRESS}
				(
					fk_user,
					fk_subject,
					s_uid,
					s_status,
					n_percentage
				) VALUES (
					:user_uuid,
					:subject_uuid,
					:uid,
					:status,
					:percentage
				)
	ON DUPLICATE KEY UPDATE
				s_status = :status,
				n_percentage = :percentage
';
$_QUERIES['sb_system/progress/getStatus'] = '
	SELECT		s_status,
				n_percentage
	FROM		{TABLE_PROGRESS}
	WHERE		fk_user = :user_uuid
		AND		fk_subject = :subject_uuid
		AND		s_uid = :uid
';
$_QUERIES['sb_system/progress/remove'] = '
	DELETE FROM	{TABLE_PROGRESS}
	WHERE		fk_user = :user_uuid
		AND		fk_subject = :subject_uuid
		AND		s_uid = :uid
';

//------------------------------------------------------------------------------
// Event log
//------------------------------------------------------------------------------

$_QUERIES['sbSystem/eventLog/LogEntry'] = '
	INSERT INTO	{TABLE_EVENTLOG}
				(
					fk_module,
					s_loguid,
					t_log,
					fk_subject,
					fk_user,
					e_type,
					dt_created
				) VALUES (
					:module,
					:loguid,
					:logtext,
					:subject,
					:user,
					:type,
					NOW()
				)
';
$_QUERIES['sbSystem/eventLog/getEntries/filtered'] = '
	SELECT 		el.id,
				el.fk_module,
				el.s_loguid,
				el.t_log,
				el.fk_subject,
				(SELECT 	s_csstype
					FROM	{TABLE_NODETYPES}
					WHERE	s_type = (SELECT	fk_nodetype
										FROM	{TABLE_NODES}
										WHERE	uuid = el.fk_subject
					)
				) AS s_subjectcsstype,
				fk_user,
				e_type,
				dt_created
	FROM		{TABLE_EVENTLOG} el
	WHERE		el.fk_module LIKE :module
		AND		el.e_type LIKE :type
	ORDER BY	el.dt_created DESC
	LIMIT		0, 500
';

//------------------------------------------------------------------------------
// Voting
//------------------------------------------------------------------------------

$_QUERIES['sbSystem/voting/placeVote'] = '
	INSERT INTO	{TABLE_VOTES}
				(
					fk_subject,
					fk_user,
					n_vote
				) VALUES (
					:subject_uuid,
					:user_uuid,
					:vote
				)
	ON DUPLICATE KEY UPDATE
				n_vote = :vote
';
$_QUERIES['sbSystem/voting/removeVote'] = '
	DELETE FROM	{TABLE_VOTES}
	WHERE		fk_subject = :subject_uuid
		AND		fk_user = :user_uuid
';
$_QUERIES['sbSystem/voting/getVote/byUser'] = '
	SELECT		n_vote
	FROM		{TABLE_VOTES}
	WHERE		fk_subject = :subject_uuid
		AND		fk_user = :user_uuid
';
$_QUERIES['sbSystem/voting/getVote/average'] = '
	SELECT		AVG(n_vote) AS n_average
	FROM		{TABLE_VOTES}
	WHERE		fk_subject = :subject_uuid
		AND		fk_user <> :ignore_uuid
	GROUP BY	fk_subject
';
$_QUERIES['sbSystem/voting/getVotes'] = '
	SELECT		fk_user,
				n_vote
	FROM		{TABLE_VOTES}
	WHERE		fk_subject = :subject_uuid
';

//------------------------------------------------------------------------------
// Tagging
//------------------------------------------------------------------------------

$_QUERIES['sbSystem/tagging/node/addTag'] = '
	INSERT INTO	{TABLE_NODETAGS}
				(
					fk_subject,
					fk_tag
				) VALUES (
					:subject_uuid,
					:tag_id
				)
';
$_QUERIES['sbSystem/tagging/node/removeTag'] = '
	DELETE FROM	{TABLE_NODETAGS}
	WHERE		fk_subject = :subject_uuid
		AND		fk_tag = :tag_id
';
$_QUERIES['sbSystem/tagging/node/getTags'] = '
	SELECT		t.id,
				t.s_tag
	FROM		{TABLE_TAGS} t
	INNER JOIN	{TABLE_NODETAGS} nt
		ON		t.id = nt.fk_tag
	WHERE		nt.fk_subject = :subject_uuid
';
$_QUERIES['sbSystem/tagging/node/getBranchTags'] = '
	SELECT		t.id,
				t.s_tag,
				t.n_popularity,
				t.n_customweight,
				COUNT(*) AS n_numitemstagged
	FROM		{TABLE_TAGS} t
	INNER JOIN	{TABLE_NODETAGS} nt
		ON		t.id = nt.fk_tag
	WHERE		t.id IN (
					SELECT		fk_tag
					FROM		{TABLE_NODETAGS} nt2
					INNER JOIN	{TABLE_NODES} n
						ON		n.uuid = nt2.fk_subject
					INNER JOIN	{TABLE_HIERARCHY} h
						ON		n.uuid = h.fk_child
					WHERE		h.n_left > (SELECT n_left
									FROM	{TABLE_HIERARCHY}
									WHERE	fk_child = :root_uuid
										AND	b_primary = \'TRUE\'
								)
						AND		h.n_right < (SELECT n_right
									FROM	{TABLE_HIERARCHY}
									WHERE	fk_child = :root_uuid
										AND	b_primary = \'TRUE\'
								)
				)
		AND		t.e_visibility <> \'HIDDEN\'
	GROUP BY	t.id
	ORDER BY	t.s_tag
';
$_QUERIES['sbSystem/tagging/tags/getID'] = '
	SELECT		id
	FROM		{TABLE_TAGS}
	WHERE		LOWER(s_tag) = LOWER(:tag)
';
$_QUERIES['sbSystem/tagging/tags/getTag'] = '
	SELECT		s_tag
	FROM		{TABLE_TAGS}
	WHERE		id = :tag_id
';
$_QUERIES['sbSystem/tagging/tags/addTag'] = '
	INSERT INTO	{TABLE_TAGS}
				(
					s_tag,
					n_popularity,
					n_customweight,
					e_visibility
				) VALUES (
					:tag,
					0,
					0,
					\'VISIBLE\'
				)
';
$_QUERIES['sbSystem/tagging/tags/updateTag'] = '
	UPDATE		{TABLE_TAGS}
	SET			s_tag = :tag,
				n_popularity = :popularity,
				n_customweight = :customweight,
				e_visibility = :visibility
		WHERE	id = :tag_id
';
$_QUERIES['sbSystem/tagging/tags/increasePopularity'] = '
	UPDATE		{TABLE_TAGS}
	SET			n_popularity = n_popularity + 1
		WHERE	id = :tag_id
';
$_QUERIES['sbSystem/tagging/tags/getAllTags'] = '
	SELECT		t.id,
				t.s_tag,
				t.n_popularity,
				t.n_customweight,
				t.e_visibility,
				(SELECT 	COUNT(*) 
					FROM	{TABLE_NODETAGS} nt
					WHERE	nt.fk_tag = t.id
				)AS n_numitemstagged
	FROM		{TABLE_TAGS} t
';
$_QUERIES['sbSystem/tagging/tags/getAllTags/orderByTag'] = $_QUERIES['sbSystem/tagging/tags/getAllTags'].'
	ORDER BY	t.s_tag
';
$_QUERIES['sbSystem/tagging/tags/getTagData'] = $_QUERIES['sbSystem/tagging/tags/getAllTags'].'
	WHERE		t.id = :tag_id
';
$_QUERIES['sbSystem/tagging/getItems/byTagID'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.fk_nodetype AS nodetype
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_NODETAGS} nt
		ON		n.uuid = nt.fk_subject
	INNER JOIN	{TABLE_HIERARCHY} h
		ON		n.uuid = h.fk_child
	WHERE		h.n_left > (SELECT n_left
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_child = :root_uuid
						AND	b_primary = \'TRUE\'
				)
		AND		h.n_right < (SELECT n_right
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_child = :root_uuid
						AND	b_primary = \'TRUE\'
				)
		AND		nt.fk_tag = :tag_id
';
$_QUERIES['sbSystem/tagging/getItems/byTagID/all'] = $_QUERIES['sbSystem/tagging/getItems/byTagID'].'
	ORDER BY	n.s_label
	LIMIT		0, :limit
';
$_QUERIES['sbSystem/tagging/getItems/byTagID/byNodetype'] = $_QUERIES['sbSystem/tagging/getItems/byTagID'].'
		AND		n.fk_nodetype = :nodetype
	ORDER BY	n.s_label
	LIMIT		0, :limit
';
$_QUERIES['sbSystem/tagging/getItems/byTagID/byNodetype/random'] = $_QUERIES['sbSystem/tagging/getItems/byTagID'].'
		AND		n.fk_nodetype = :nodetype
	ORDER BY	RAND()
	LIMIT		0, :limit
';
$_QUERIES['sbSystem/tagging/clearUnusedTags'] = '
	DELETE FROM {TABLE_TAGS}
	WHERE		id NOT IN (
					SELECT	fk_tag
					FROM	{TABLE_NODETAGS}
				)
';

// action- & view-related ------------------------------------------------------
$_QUERIES['sbSystem/node/loadActionDetails/given'] = '
	SELECT		*
	FROM		{TABLE_ACTIONS} a
	INNER JOIN	{TABLE_NODES} n
		ON		a.fk_nodetype = n.fk_nodetype
	WHERE		n.uuid = :uuid
		AND		a.s_view = :view
		AND		a.s_action = :action
';
$_QUERIES['sbSystem/node/loadActionDetails/default'] = '
	SELECT		*
	FROM		{TABLE_ACTIONS} a
	INNER JOIN	{TABLE_NODES} n
		ON		a.fk_nodetype = n.fk_nodetype
	WHERE		n.uuid = :uuid
		AND		a.s_view = :view
		AND		a.b_default = \'TRUE\'
';


//------------------------------------------------------------------------------
// node:user 
//------------------------------------------------------------------------------
// view:edit -------------------------------------------------------------------
$_QUERIES['sbSystem/user/loadProperties/auxiliary'] = '
	SELECT		s_password,
				s_email,
				t_comment,
				b_activated,
				b_stayloggedin,
				b_locked,
				b_emailsent,
				s_activationkey,
				dt_activatedat,
				dt_currentlogin,
				dt_lastlogin,
				b_hidestatus,
				n_failedlogins,
				n_successfullogins,
				n_silentlogins,
				n_totalfailedlogins,
				b_backendaccess
	FROM		{TABLE_USERS}
	WHERE		uuid = :node_id
';
$_QUERIES['sbSystem/user/saveProperties/auxiliary'] = '
	INSERT INTO	{TABLE_USERS}
				(
					s_password,
					s_email,
					t_comment,
					b_activated,
					b_stayloggedin,
					b_locked,
					b_emailsent,
					s_activationkey,
					dt_activatedat,
					dt_currentlogin,
					dt_lastlogin,
					b_hidestatus,
					n_failedlogins,
					n_successfullogins,
					n_silentlogins,
					n_totalfailedlogins,
					b_backendaccess,
					uuid
				) VALUES (
					:security_password,
					:properties_email,
					:properties_comment,
					:security_activated,
					:security_stayloggedin,
					:security_locked,
					:info_emailsent,
					:security_activationkey,
					:info_activatedat,
					:info_currentlogin,
					:info_lastlogin,
					:config_hidestatus,
					:security_failedlogins,
					:info_successfullogins,
					:info_silentlogins,
					:info_totalfailedlogins,
					:security_backendaccess,
					:node_id
				)
	ON DUPLICATE KEY UPDATE
				s_password = :security_password,
				s_email = :properties_email,
				t_comment = :properties_comment,
				b_activated = :security_activated,
				b_stayloggedin = :security_stayloggedin,
				b_locked = :security_locked,
				b_emailsent = :info_emailsent,
				s_activationkey = :security_activationkey,
				dt_activatedat = :info_activatedat,
				dt_currentlogin = :info_currentlogin,
				dt_lastlogin = :info_lastlogin,
				b_hidestatus = :config_hidestatus,
				n_failedlogins = :security_failedlogins,
				n_successfullogins = :info_successfullogins,
				n_silentlogins = :info_silentlogins,
				n_totalfailedlogins = :info_totalfailedlogins
';

//------------------------------------------------------------------------------
// node:userentity 
//------------------------------------------------------------------------------
// view:edit -------------------------------------------------------------------
$_QUERIES['sb_system/userentity/getAuthorisations'] = '
	SELECT		a.fk_subject,
				a.fk_authorisation,
				a.e_granttype
	FROM		{TABLE_AUTH} a
	WHERE		a.fk_userentity = :uuid
';

//------------------------------------------------------------------------------
// node:maintenance
//------------------------------------------------------------------------------
// view:repair -----------------------------------------------------------------
$_QUERIES['sbSystem/maintenance/view/repair/loadRoot'] = '
	SELECT		uuid
	FROM		{TABLE_NODES}
	WHERE		s_uid = \'sb_system:root\'
';
$_QUERIES['sbSystem/maintenance/view/repair/loadChildren'] = '
	SELECT		fk_child as uuid
	FROM		{TABLE_HIERARCHY}
	WHERE		fk_parent = :fk_parent
		AND		fk_child <> \'00000000000000000000000000000000\'
	ORDER BY	n_order
';
$_QUERIES['sbSystem/maintenance/view/repair/setCoordinates'] = '
	UPDATE 		{TABLE_HIERARCHY}
	SET			n_left = :left,
				n_right = :right,
				n_level = :level,
				n_order = :order
	WHERE		fk_child = :fk_child
		AND		fk_parent = :fk_parent
';
$_QUERIES['sbSystem/maintenance/view/repair/checkAbandonedProperties'] = '
	SELECT 		*
	FROM		{TABLE_PROPERTIES} p
	LEFT JOIN	{TABLE_NODES} n
		ON		p.fk_node = n.uuid
	WHERE		n.uuid IS NULL
	UNION 
	SELECT 		*
	FROM		{TABLE_BINPROPERTIES} pb
	LEFT JOIN	{TABLE_NODES} n
		ON		pb.fk_node = n.uuid
	WHERE		n.uuid IS NULL
';
$_QUERIES['sbSystem/maintenance/view/repair/removeAbandonedProperties/normal'] = '
	DELETE FROM	{TABLE_PROPERTIES}
	WHERE		fk_node NOT IN (
					SELECT	uuid
					FROM	{TABLE_NODES}
				)
';
$_QUERIES['sbSystem/maintenance/view/repair/removeAbandonedProperties/binary'] = '
	DELETE FROM	{TABLE_BINPROPERTIES}
	WHERE		fk_node NOT IN (
					SELECT	uuid
					FROM	{TABLE_NODES}
				)
';
$_QUERIES['sbSystem/maintenance/view/repair/removeAbandonedNodes/normal'] = '
	DELETE FROM	{PREFIX_WORKSPACE}_system_nodes
	WHERE		uuid NOT IN (
					SELECT	fk_child
					FROM	{TABLE_HIERARCHY}
				)
		AND		uuid NOT IN (
					SELECT	fk_parent
					FROM	{TABLE_HIERARCHY}
				)
';

//------------------------------------------------------------------------------
// node:folder
//------------------------------------------------------------------------------
// view:upload -----------------------------------------------------------------
$_QUERIES['sb_system/folder/view/upload/getMimetypeMapping'] = '
	SELECT		s_mimetype,
				fk_nodetype
	FROM		{TABLE_MIMETYPES}
';

//------------------------------------------------------------------------------
// node:preferences
//------------------------------------------------------------------------------
// view:moduleinfo -------------------------------------------------------------
$_QUERIES['sb_system/modules/getInfo'] = '
	SELECT		*
	FROM		{TABLE_MODULES}
';

//------------------------------------------------------------------------------
// node:reports_structure
//------------------------------------------------------------------------------
// view:nodetypes --------------------------------------------------------------
$_QUERIES['sb_system/reports_structure/nodetypes/overview'] = '
	SELECT		nt.s_type,
				nt.s_csstype,
				(SELECT
					COUNT(*) 
					FROM	{TABLE_NODES}
					WHERE	fk_nodetype = nt.s_type
				) AS num_nodes,
				(SELECT
					COUNT(*) 
					FROM		{TABLE_NODES} n
					LEFT JOIN	{TABLE_HIERARCHY} h
						ON		n.uuid = h.fk_child
					WHERE		fk_nodetype = nt.s_type
						AND		h.fk_child IS NULL
				) AS num_lostnodes
	FROM		{TABLE_NODETYPES} nt
';

//------------------------------------------------------------------------------
// node:reports_db
//------------------------------------------------------------------------------
// view:tables -----------------------------------------------------------------
$_QUERIES['sb_system/reports_db/tables/overview'] = '
	SHOW TABLE STATUS
';
/*
$_QUERIES['sb_system/reports_db/tables/details'] = '
	SHOW TABLE STATUS LIKE :table_name
';*/
// view:status -----------------------------------------------------------------
$_QUERIES['sb_system/reports_db/status/variables'] = '
	SHOW VARIABLES
';
$_QUERIES['sb_system/reports_db/status/status'] = '
	SHOW STATUS
';

//------------------------------------------------------------------------------
// node:trashcan
//------------------------------------------------------------------------------

$_QUERIES['sbSystem/node/trashcan/getAbandonedNodes'] = '
	SELECT		n.uuid,
				n.fk_nodetype,
				n.s_name,
				n.s_label,
				n.s_customcsstype,
				nt.s_csstype
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_NODETYPES} nt
		ON		n.fk_nodetype = nt.s_type
	WHERE		(
					SELECT 	COUNT(*)
					FROM	{TABLE_HIERARCHY} h
					WHERE	fk_child = n.uuid
				) = 0
	AND			n.fk_nodetype != \'sb_system:root\'
';
/*$_QUERIES['sb_system/node/loadChildren/trashcan'] = '
	SELECT		sn.uuid,
				sn.fk_nodetype,
				sn.s_name,
				sn.s_label,
				sn.s_customcsstype,
				snt.s_csstype
	FROM		{PREFIX_WORKSPACE}_system_nodes sn
	INNER JOIN	{PREFIX_FRAMEWORK}_system_nodetypes snt
		ON		sn.fk_nodetype = snt.s_type
	WHERE		(
					SELECT 	COUNT(*)
					FROM	{PREFIX_WORKSPACE}_system_nodes_parents snp
					WHERE	fk_child = sn.uuid
				) = 0
';*/

//------------------------------------------------------------------------------
// node:registry
//------------------------------------------------------------------------------
// view:edit -------------------------------------------------------------------
$_QUERIES['sbSystem/registry/getAllEntries'] = '
	SELECT		*
	FROM		{TABLE_REGISTRY}
	ORDER BY	s_key
';
$_QUERIES['sbSystem/registry/getValue'] = '
	SELECT		s_value,
				e_type
	FROM		{TABLE_REGISTRY}
	WHERE		s_key = :key
		AND		fk_user = :user_uuid
';
$_QUERIES['sbSystem/registry/setValue'] = '
	UPDATE		{TABLE_REGISTRY}
	SET			s_value = :value
	WHERE		s_key = :key
		AND		fk_user = :user_uuid
';


//------------------------------------------------------------------------------
// node:root 
//------------------------------------------------------------------------------
// view:welcome ----------------------------------------------------------------
$_QUERIES['sb_system/root/view/welcome/loadUserdata'] = '
	SELECT		n.s_label AS s_nickname,
				u.b_activated,
				u.dt_lastlogin,
				u.dt_currentlogin,
				u.n_totalfailedlogins,
				u.n_successfullogins
	FROM		{TABLE_USERS} u
	INNER JOIN	{TABLE_NODES} n
		ON		n.uuid = u.uuid
	WHERE		n.uuid = :user_id
';

// view:login ------------------------------------------------------------------
$_QUERIES['sb_system/root/view/login/loadUserdata'] = '
	SELECT		u.uuid,
				n.s_label AS s_nickname,
				u.s_password,
				u.b_activated,
				u.b_locked,
				u.dt_currentlogin,
				u.n_failedlogins,
				u.dt_failedlogin
	FROM		{TABLE_USERS} u
	INNER JOIN	{TABLE_NODES} n
		ON		n.uuid = u.uuid
	WHERE		n.s_name= :login
';
$_QUERIES['sb_system/root/view/login/increaseFailedLogins'] = '
	UPDATE		{TABLE_USERS}
	SET			n_failedlogins = n_failedlogins + 1,
				n_totalfailedlogins = n_totalfailedlogins + 1,
				dt_failedlogin = NOW()
	WHERE		uuid = :user_id
';
$_QUERIES['sb_system/root/view/login/resetFailedLogins'] = '
	UPDATE		{TABLE_USERS}
	SET			n_failedlogins = 0,
				dt_failedlogin = NULL
	WHERE		uuid = :user_id
';
$_QUERIES['sb_system/root/view/login/successfulLogin'] = '
	UPDATE		{TABLE_USERS}
	SET			n_failedlogins		= 0,
				dt_lastlogin		= dt_currentlogin,
				dt_currentlogin		= NOW(),
				n_successfullogins	= n_successfullogins + 1
	WHERE		uuid = :user_id
';

//------------------------------------------------------------------------------
// node:debug 
//------------------------------------------------------------------------------
$_QUERIES['sbSystem/debug/gatherTree'] = '
	SELECT		n.uuid,
				n.s_name,
				h.n_left,
				h.n_right,
				h.n_level,
				h.n_order,
				nt.s_type,
				(SELECT COUNT(*)
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_parent = n.uuid
				) as n_numchildren
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_NODETYPES} nt
		ON		n.fk_nodetype = nt.s_type
	INNER JOIN	{TABLE_HIERARCHY} h
		ON		n.uuid = h.fk_child
	/*INNER JOIN	{TABLE_NODES} n2
		ON		h.fk_parent = n2.uuid*/
	WHERE		h.fk_parent = :parent_uuid
		AND		h.fk_child <> \'00000000000000000000000000000000\'
	ORDER BY	h.n_left ASC
';

?>