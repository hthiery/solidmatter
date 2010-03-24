<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	version="1.0" 
	exclude-result-prefixes="html sbform php" 
	exclude-element-prefixes="html sbform" 
	xmlns:html="http://www.w3.org/1999/xhtml"
	xmlns:sbform="http://www.solidbytes.net/sbform"
	xmlns:dyn="http://exslt.org/dynamic" 
	extension-element-prefixes="dyn"
	xmlns:php="http://php.net/xsl"
	>

	<xsl:import href="global.default.xsl" />
	
	<xsl:output 
		method="html"
		encoding="UTF-8"
		standalone="yes"
		indent="yes"
		doctype-system="http://www.w3.org/TR/html4/loose.dtd" 
		doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN"
	/>
	
	<xsl:template match="/">
		<xsl:call-template name="layout" />
	</xsl:template>
	
	<xsl:template name="content">
		<div class="toolbar">
			<xsl:call-template name="import">
				<xsl:with-param name="form" select="$content/sbform[@id='importM3U']" />
			</xsl:call-template>
		</div>
		<div class="nav">
			<span style="float: right;">
				<span style="margin-left: 15px;"></span>
				
			</span>
			<xsl:if test="$master/user_authorisations/authorisation[@name='write' and @grant_type='ALLOW']">
				<a class="type remove" href="/{$master/@uuid}/details/clear"><xsl:value-of select="$locale/sbSystem/actions/remove_all" /></a>
			</xsl:if>
		</div>
		<div class="content">
			<xsl:apply-templates select="response/errors" />
			<xsl:apply-templates select="$content/sbnode[@master]" />
		</div>
	</xsl:template>
	
	<xsl:template match="sbnode">
			
		<div class="th">
			<div class="albumdetails" style="float:right;">
				<xsl:if test="@nodetype='sbJukebox:Playlist' and $master/user_authorisations/authorisation[@name='add_titles' and @grant_type='ALLOW']">
					<xsl:choose>
						<xsl:when test="@uuid = $currentPlaylist/@uuid">
							<a class="type activated icononly" href="/{@uuid}/details/activate" title="{$locale/sbJukebox/actions/activate}"><img src="/theme/sb_jukebox/icons/blank.gif" alt="Dummy" /></a>
						</xsl:when>
						<xsl:otherwise>
							<a class="type activate icononly" href="/{@uuid}/details/activate" title="{$locale/sbJukebox/actions/activate}"><img src="/theme/sb_jukebox/icons/blank.gif" alt="Dummy" /></a>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:if>
				<span style="margin-left: 15px;"></span>
				<xsl:call-template name="render_buttons"/>
				<span style="margin-left: 15px;"></span>
				<xsl:call-template name="render_stars" />
				<span style="margin-left: 5px;"></span>
				<xsl:call-template name="render_votebuttons" />
			</div>
			<span class="type playlist"><xsl:value-of select="@label" /></span>
		</div>
		
		<ul class="sortable" width="100%" id="playlist">
			<xsl:choose>
				<xsl:when test="children[@mode='tracks']/sbnode">
					<xsl:for-each select="children[@mode='tracks']/sbnode">
						<li style="position:relative;top:0;left:0;" id="item_{@uuid}">
							<xsl:call-template name="colorize" />
							<xsl:if test="$master/user_authorisations/authorisation[@name='write' and @grant_type='ALLOW']">
								<a style="position:absolute;top:3px;right:3px;" class="type remove icononly" href="javascript:remove('{@uuid}')" title="{$locale/sbJukebox/actions/remove}"><img src="/theme/sb_jukebox/icons/blank.gif" alt="Dummy" /></a>
							</xsl:if>
							<a href="/{@uuid}"><xsl:value-of select="@label" /></a>
						</li>
					</xsl:for-each>
				</xsl:when>
				<xsl:otherwise>
					<!--<li><xsl:value-of select="$locale/sbSystem/texts/no_subobjects" /></li>-->
				</xsl:otherwise>
			</xsl:choose>
		</ul>
		
		<script language="Javascript">
			
			//--------------------------------------------------------------
			// init
			//
			<xsl:if test="$master/user_authorisations/authorisation[@name='write' and @grant_type='ALLOW']">
			var oPlaylist = $('playlist');
			var aInitialState = getOrder(oPlaylist);
			Sortable.create('playlist', { onChange: redraw, onUpdate: reorder } );
			</xsl:if>
			
			//--------------------------------------------------------------
			// remove an entry and fade it out
			//
			function remove(sUUID) {
				
				var sURL = "/<xsl:value-of select="$master/@uuid" />/details/removeItem/?item=" + sUUID + '&amp;silent';
				var myAjaxRemover = new Ajax.Request(
					sURL, 
					{
						method: 'get', 
						parameters: null
					}
				);
				$('item_' + sUUID).fade({ afterFinish: redraw });
				
			}
			
			//--------------------------------------------------------------
			// redraw resp. recolor all list entries
			//
			function redraw() {
				
				var sClass = 'odd';
				var eChildren = $('playlist').childElements();
				for (var i=0; i&lt;eChildren.length; i++) {
					if (eChildren[i].style.display == 'none') {
						continue;
					}
					eChildren[i].className = sClass;
					if (sClass == 'odd') {
						sClass = 'even';
					} else {
						sClass = 'odd';
					}
				}
				
			}
			
			//--------------------------------------------------------------
			// callback on dragging and dropping an item
			//
			function reorder(info) {
				
				var aCurrentState = getOrder(oPlaylist);
				var sSubject = '';
				var sNextSibling = '';
				
				for (var i=0; i&lt;aCurrentState.length; i++) {
					if (aInitialState[i] != aCurrentState[i]) { // different item in lists
						if (aCurrentState[i] == aInitialState[i+1] &amp;&amp; aCurrentState[i+1] == aInitialState[i]) { // items switched
							//alert('switched');
							sSubject = aCurrentState[i];
							sNextSibling = aCurrentState[i+1];
							update(sSubject, sNextSibling);
							break;
						} else if (aCurrentState[i] == aInitialState[i+1]) { // missing item = moved down
							for (var j=i; j&lt;aCurrentState.length; j++) { // find missing item
								if (aCurrentState[j] != aInitialState[j+1]) {
									if (!aCurrentState[j+1]) { // item moved to end of list
										//alert('moved to end of list');
										sPreviousSibling = aCurrentState[j-1];
										sSubject = aCurrentState[j];
										update(sSubject, sPreviousSibling); // move item just before last item
										update(sPreviousSibling, sSubject); // flip the two
									} else { // item moved down
										//alert('moved down');
										sSubject = aCurrentState[j];
										sNextSibling = aCurrentState[j+1];
										update(sSubject, sNextSibling);
									}
									break;
								}
							}
							break;
						} else { // item moved up
							//alert('moved up');
							sSubject = aCurrentState[i];
							sNextSibling = aCurrentState[i+1];
							update(sSubject, sNextSibling);
							break;
						}
					}
				}
				
				aInitialState = aCurrentState;
			
			}
			
			//--------------------------------------------------------------
			// assistant function the saves a change
			//
			function update (sSubject, sNextSibling) {
				
				sURL = "/<xsl:value-of select="$master/@uuid" />/details/orderBefore/?subject=" + sSubject + "&amp;nextsibling=" + sNextSibling;
				var myAjaxUpdater = new Ajax.Request(
					sURL, 
					{
						method: 'get', 
						parameters: null,
						asynchronous: true 
					}
				);
				
			}
			
			//--------------------------------------------------------------
			// gets an array with the current ordered uuids
			//
			function getOrder() {
				
				var aCurrentOrder = new Array();
				var aOrderedNodes = oPlaylist.getElementsByTagName("li");
				for (var i=0; i&lt;aOrderedNodes.length; i++) {
					aCurrentOrder[i] = aOrderedNodes[i].getAttribute('id').substr(5);
				}
				
				return (aCurrentOrder);
				
			}
			
		</script>
			
		<xsl:call-template name="comments" />
		
	</xsl:template>
	
	<xsl:template name="import">
		<xsl:param name="form" />
		<!--<xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>-->
		<form action="{$form/@action}" name="import" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
			<xsl:value-of select="$locale/sbJukebox/labels/upload_playlist" />:
			<xsl:apply-templates select="$form/sbinput[@type='fileupload']" mode="inputonly" />
			<xsl:value-of select="' '" />
			<xsl:apply-templates select="$form/submit" mode="inputonly" />
		</form>
	</xsl:template>

</xsl:stylesheet>