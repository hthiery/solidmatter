<?php

//------------------------------------------------------------------------------
/**
*	@package solidMatter[sbJukebox]
*	@author	()((() [Oliver Müller]
*	@version 1.00.00
*/
//------------------------------------------------------------------------------

global $_QUERIES;

$_QUERIES['MAPPING']['{TABLE_JB_ALBUMS}']			= '{PREFIX_WORKSPACE}_jukebox_albums';
$_QUERIES['MAPPING']['{TABLE_JB_TRACKS}']			= '{PREFIX_WORKSPACE}_jukebox_tracks';
$_QUERIES['MAPPING']['{TABLE_JB_ARTISTS}']			= '{PREFIX_WORKSPACE}_jukebox_artists';
$_QUERIES['MAPPING']['{TABLE_JB_VOTES}']			= '{PREFIX_WORKSPACE}_jukebox_votes';
$_QUERIES['MAPPING']['{TABLE_JB_GENRES}']			= '{PREFIX_WORKSPACE}_jukebox_genres';
$_QUERIES['MAPPING']['{TABLE_JB_TRACKSGENRES}']		= '{PREFIX_WORKSPACE}_jukebox_tracks_genres';
$_QUERIES['MAPPING']['{TABLE_JB_BLACKLIST}']		= '{PREFIX_WORKSPACE}_jukebox_blacklist';
$_QUERIES['MAPPING']['{TABLE_JB_NOWPLAYING}']		= '{PREFIX_WORKSPACE}_jukebox_nowplaying';

//------------------------------------------------------------------------------
// nowplaying
//------------------------------------------------------------------------------

$_QUERIES['sbJukebox/nowPlaying/set'] = '
	INSERT INTO	{TABLE_JB_NOWPLAYING}
				(
					fk_user,
					fk_track,
					n_playtime,
					dt_played
				) VALUES (
					:user_uuid,
					:track_uuid,
					:playtime,
					NOW()
				)
	ON DUPLICATE KEY UPDATE
				fk_track = :track_uuid,
				n_playtime = :playtime,
				dt_played = NOW()
';
$_QUERIES['sbJukebox/nowPlaying/get'] = '
	SELECT		p.fk_track AS uuid,
				n.s_label AS label,
				p.fk_user AS useruuid,
				n2.s_label AS username
	FROM		{TABLE_JB_NOWPLAYING} p
	INNER JOIN	{TABLE_NODES} n
		ON		n.uuid = p.fk_track
	INNER JOIN	{TABLE_NODES} n2
		ON		n2.uuid = p.fk_user
	ORDER BY	n.s_label
';
$_QUERIES['sbJukebox/nowPlaying/clear'] = '
	DELETE FROM	{TABLE_JB_NOWPLAYING}
	WHERE		UNIX_TIMESTAMP() - UNIX_TIMESTAMP(dt_played) > :seconds
		AND		UNIX_TIMESTAMP() - UNIX_TIMESTAMP(dt_played) > n_playtime
';

//------------------------------------------------------------------------------
// jukebox
//------------------------------------------------------------------------------

$_QUERIES['sbJukebox/jukebox/gatherInfo'] = '
	SELECT		(SELECT COUNT(*)
					FROM		{TABLE_HIERARCHY} h1
					INNER JOIN	{TABLE_NODES} n
						ON		h1.fk_child = n.uuid
					WHERE		h1.n_left > h.n_left
						AND		h1.n_right < h.n_right
						AND		n.fk_nodetype = \'sb_jukebox:album\'
				) AS n_numalbums,
				(SELECT COUNT(*)
					FROM		{TABLE_HIERARCHY} h1
					INNER JOIN	{TABLE_NODES} n
						ON		h1.fk_child = n.uuid
					WHERE		fk_parent = :jukebox_uuid
						AND		n.fk_nodetype = \'sb_jukebox:artist\'
				) AS n_numartists,
				(SELECT COUNT(*)
					FROM		{TABLE_HIERARCHY} h1
					INNER JOIN	{TABLE_NODES} n
						ON		h1.fk_child = n.uuid
					WHERE		h1.n_left > h.n_left
						AND		h1.n_right < h.n_right
						AND		n.fk_nodetype = \'sb_jukebox:track\'
				) AS n_numtracks,
				(SELECT COUNT(*)
					FROM		{TABLE_HIERARCHY} h1
					INNER JOIN	{TABLE_NODES} n
						ON		h1.fk_child = n.uuid
					WHERE		h1.n_left > h.n_left
						AND		h1.n_right < h.n_right
						AND		n.fk_nodetype = \'sb_jukebox:playlist\'
				) AS n_numplaylists
	FROM		{TABLE_HIERARCHY} h
	WHERE		h.fk_child = :jukebox_uuid
';
$sHierarchyComponent = '
	INNER JOIN	{TABLE_HIERARCHY} h
		ON		n.uuid = h.fk_child
	WHERE		h.n_left > (SELECT n_left
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_child = :jukebox_uuid
						AND	b_primary = \'TRUE\'
				)
		AND		h.n_right < (SELECT n_right
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_child = :jukebox_uuid
						AND	b_primary = \'TRUE\'
				)
';
$_QUERIES['sbJukebox/jukebox/search/anything/byLabel'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name,
				n.fk_nodetype AS nodetype
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_NODETYPES} nt
		ON		n.fk_nodetype = nt.s_type
				'.$sHierarchyComponent.'
		AND		n.s_label LIKE :searchstring
	ORDER BY	n.fk_nodetype,
				n.s_label
';
$_QUERIES['sbJukebox/jukebox/search/various/byLabel'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name,
				(SELECT 	n_vote 
					FROM	{TABLE_VOTES} v
					WHERE	v.fk_subject = n.uuid
						AND	v.fk_user = :user_uuid
				) AS vote
	FROM		{TABLE_NODES} n
				'.$sHierarchyComponent.'
		AND		n.fk_nodetype = :nodetype
		AND		n.s_label LIKE :searchstring
	ORDER BY	n.s_label
';
$_QUERIES['sbJukebox/jukebox/search/various/numeric'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name,
				(SELECT 	n_vote 
					FROM	{TABLE_VOTES} v
					WHERE	v.fk_subject = n.uuid
						AND	v.fk_user = :user_uuid
				) AS vote
	FROM		{TABLE_NODES} n
				'.$sHierarchyComponent.'
		AND		n.fk_nodetype = :nodetype
		AND		n.s_label REGEXP \'^[0-9]\'
	ORDER BY	n.s_label
';
$_QUERIES['sbJukebox/jukebox/search/albums/byLabel'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name,
				a.b_coverexists,
				(SELECT 	n_vote 
					FROM	{TABLE_VOTES} v
					WHERE	v.fk_subject = n.uuid
						AND	v.fk_user = :user_uuid
				) AS vote
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_JB_ALBUMS} a
		ON		n.uuid = a.uuid
				'.$sHierarchyComponent.'
		AND		n.s_label LIKE :searchstring
	ORDER BY	n.s_label
';
$_QUERIES['sbJukebox/jukebox/search/albums/numeric'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name,
				a.b_coverexists,
				(SELECT 	n_vote 
					FROM	{TABLE_VOTES} v
					WHERE	v.fk_subject = n.uuid
						AND	v.fk_user = :user_uuid
				) AS vote
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_JB_ALBUMS} a
		ON		n.uuid = a.uuid
				'.$sHierarchyComponent.'
		AND		n.s_label REGEXP \'^[0-9]\'
	ORDER BY	n.s_label
';
$_QUERIES['sbJukebox/jukebox/albums/getRandom'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name,
				a.b_coverexists,
				(SELECT 	n_vote 
					FROM	{TABLE_VOTES} v
					WHERE	v.fk_subject = n.uuid
						AND	v.fk_user = :user_uuid
				) AS vote
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_JB_ALBUMS} a
		ON		n.uuid = a.uuid
				'.$sHierarchyComponent.'
	ORDER BY	RAND()
	LIMIT		0, :limit
';
$_QUERIES['sbJukebox/jukebox/albums/getLatest'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name,
				a.b_coverexists,
				(SELECT 	n_vote 
					FROM	{TABLE_VOTES} v
					WHERE	v.fk_subject = n.uuid
						AND	v.fk_user = :user_uuid
				) AS vote
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_JB_ALBUMS} a
		ON		n.uuid = a.uuid
				'.$sHierarchyComponent.'
		AND		n.fk_nodetype = :nodetype
	ORDER BY	n.dt_createdat DESC
	LIMIT		0, :limit
';

//------------------------------------------------------------------------------
// artist
//------------------------------------------------------------------------------

$_QUERIES['sbJukebox/artist/getTracks/differentAlbums'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				t.s_playtime AS playtime,
				n2.uuid AS albumuuid,
				n2.s_label as albumlabel
	FROM		{TABLE_JB_TRACKS} jt
	INNER JOIN	{TABLE_NODES} n
		ON		n.uuid = jt.uuid
	INNER JOIN	{TABLE_HIERARCHY} h
		ON		n.uuid = h.fk_child
	INNER JOIN	{TABLE_NODES} n2
		ON		n2.uuid = h.fk_parent
	INNER JOIN	{TABLE_JB_TRACKS} t
		ON		n.uuid = t.uuid
	WHERE		h.n_left > (SELECT n_left
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_child = :jukebox_uuid
						AND	b_primary = \'TRUE\'
				)
		AND		h.n_right < (SELECT n_right
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_child = :jukebox_uuid
						AND	b_primary = \'TRUE\'
				)
		AND		jt.fk_artist = :artist_uuid
		AND		n2.fk_nodetype = \'sb_jukebox:album\'
		AND		h.fk_parent NOT IN (
					SELECT	fk_child
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_parent = :artist_uuid
				)
	ORDER BY	n2.s_label, n.s_label
	LIMIT		0, :limit
';

//------------------------------------------------------------------------------
// album
//------------------------------------------------------------------------------

$_QUERIES['sbJukebox/album/properties/load/auxiliary'] = '
	SELECT		fk_artist,
				s_title,
				n_published,
				n_cdsinset,
				b_coverexists,
				s_coverfilename,
				n_coverlightness,
				n_coverhue,
				n_coversaturation,
				s_relpath,
				e_type,
				e_defects
	FROM		{TABLE_JB_ALBUMS}
	WHERE		uuid = :node_id
';
$_QUERIES['sbJukebox/album/properties/save/auxiliary'] = '
	INSERT INTO {TABLE_JB_ALBUMS}
				(
					uuid,
					fk_artist,
					s_title,
					n_published,
					n_cdsinset,
					b_coverexists,
					s_coverfilename,
					n_coverhue,
					n_coversaturation,
					n_coverlightness,
					s_relpath,
					e_type,
					e_defects
				) VALUES (
					:node_id,
					:info_artist,
					:info_title,
					:info_published,
					:info_cdsinset,
					:info_coverexists,
					:info_coverfilename,
					:ext_coverhue,
					:ext_coversaturation,
					:ext_coverlightness,
					:info_relpath,
					:info_type,
					:info_defects
				)
	ON DUPLICATE KEY UPDATE
				fk_artist = :info_artist,
				s_title = :info_title,
				n_published = :info_published,
				n_cdsinset = :info_cdsinset,
				b_coverexists = :info_coverexists,
				s_coverfilename = :info_coverfilename,
				n_coverhue = :ext_coverhue,
				n_coversaturation = :ext_coversaturation,
				n_coverlightness = :ext_coverlightness,
				s_relpath = :info_relpath,
				e_type = :info_type,
				e_defects = :info_defects
';
/*$_QUERIES['sbJukebox/album/quilt/findCover'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_JB_ALBUMS} a
		ON		n.uuid = a.uuid
				'.$sHierarchyComponent.'
		AND		a.n_coverluminance > :luminance - :tolerance
		AND		a.n_coverluminance < :luminance + :tolerance
	ORDER BY 	RAND()
	LIMIT		0, 1
	
	ROUND(ABS(a.n_coverlightness - :lightness) / 8),
				ROUND(ABS(a.n_coverhue - :hue) / 8),
				ABS(a.n_coverlightness - :lightness),
				ABS(a.n_coverhue - :hue)
	
';*/
$_QUERIES['sbJukebox/album/quilt/findCover'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_JB_ALBUMS} a
		ON		n.uuid = a.uuid
				'.$sHierarchyComponent.'
	ORDER BY 	ROUND(ABS(a.n_coverlightness - :lightness) / 4) + 
				ROUND(ABS(a.n_coverhue - :hue) / 8) + 
				ROUND(ABS(a.n_coversaturation - :saturation) / 12),
				ABS(a.n_coverlightness - :lightness),
				ABS(a.n_coverhue - :hue),
				ABS(a.n_coversaturation - :saturation)
	LIMIT		0, 1
';

//------------------------------------------------------------------------------
// track
//------------------------------------------------------------------------------

$_QUERIES['sbJukebox/track/properties/load/auxiliary'] = '
	SELECT		jt.s_title,
				jt.s_filename,
				jt.fk_artist,
				jt.n_index,
				jt.n_published,
				jt.s_playtime,
				jt.n_playtime,
				jt.s_mode,
				jt.n_bitrate,
				/*CONCAT(
					(SELECT s_relpath 
						FROM {PREFIX_WORKSPACE}_jukebox_albums
						WHERE uuid = (SELECT fk_parent 
							FROM	{PREFIX_WORKSPACE}_system_nodes_parents
							WHERE	fk_child = :node_id
								AND	b_primary = \'TRUE\'
						)
					),
					jt.s_filename
				) AS s_fullpath*/
				\'DEACTIVATED\' AS s_fullpath
	FROM		{TABLE_JB_TRACKS} jt
	WHERE		jt.uuid = :node_id
';
$_QUERIES['sbJukebox/track/properties/save/auxiliary'] = '
	INSERT INTO {TABLE_JB_TRACKS}
				(
					uuid,
					s_filename,
					fk_artist,
					s_title,
					n_index,
					n_published,
					s_playtime,
					n_playtime,
					s_mode,
					n_bitrate
				) VALUES (
					:node_id,
					:info_filename,
					:info_artist,
					:info_title,
					:info_index,
					:info_published,
					:info_playtime,
					:enc_playtime,
					:enc_mode,
					:enc_bitrate
				)
	ON DUPLICATE KEY UPDATE
				s_filename = :info_filename,
				fk_artist = :info_artist,
				n_index = :info_index,
				n_published = :info_published,
				s_playtime = :info_playtime,
				n_playtime = :enc_playtime,
				s_mode = :enc_mode,
				n_bitrate = :enc_bitrate
';

//------------------------------------------------------------------------------
// various
//------------------------------------------------------------------------------

$_QUERIES['sbJukebox/jukebox/various/getRandom'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name,
				v.n_vote AS vote
	FROM		{TABLE_NODES} n
	LEFT JOIN	{TABLE_VOTES} v
		ON		n.uuid = v.fk_subject
				'.$sHierarchyComponent.'
		AND		n.fk_nodetype = :nodetype
	ORDER BY	RAND()
	LIMIT		0, :limit
';
$_QUERIES['sbJukebox/jukebox/various/getTop'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.s_name AS name,
				v.n_vote AS vote
	FROM		{TABLE_NODES} n
	LEFT JOIN	{TABLE_VOTES} v
		ON		n.uuid = v.fk_subject
				'.$sHierarchyComponent.'
		AND		n.fk_nodetype = :nodetype
		AND		v.fk_user = :user_uuid
	ORDER BY	n_vote DESC
	LIMIT		0, :limit
';

//------------------------------------------------------------------------------
// temp?
//------------------------------------------------------------------------------

$_QUERIES['sbJukebox/jukebox/getVoters'] = '
	SELECT		n.uuid,
				n.s_label AS label,
				n.fk_nodetype
	FROM		{TABLE_NODES} n
	INNER JOIN	{TABLE_VOTES} v
		ON		n.uuid = v.fk_user
	INNER JOIN	{TABLE_HIERARCHY} h
		ON		v.fk_subject = h.fk_child
	WHERE		h.n_left > (SELECT n_left
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_child = :jukebox_uuid
						AND	b_primary = \'TRUE\'
				)
		AND		h.n_right < (SELECT n_right
					FROM	{TABLE_HIERARCHY}
					WHERE	fk_child = :jukebox_uuid
						AND	b_primary = \'TRUE\'
				)
	ORDER BY	n.s_label
';


