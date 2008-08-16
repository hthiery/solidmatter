<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	version="1.0" 
	exclude-result-prefixes="html" 
	xmlns:html="http://www.w3.org/1999/xhtml">

	<xsl:import href="global.default.xsl" />
		
	<xsl:output 
		method="html"
		encoding="UTF-8"
		standalone="yes"
		indent="no"
	/>
	
	<xsl:template match="/">
		<xsl:apply-templates />
	</xsl:template>
	
	<xsl:template match="/response/content/menu">
		<xsl:choose>
			<xsl:when test="sbnode/@type='root'">
				<html>
					<head>
						<link rel="stylesheet" href="{$stylesheets_css}/styles_tree.css" type="text/css" media="all" />
						<script type="text/javascript" src="{$scripts_js}/prototype/prototype_full.js"></script>
					</head>
					<body>
						<div class="tree">
							<ul class="tree">
								<xsl:call-template name="menuentry"/>
							</ul>
						</div>
					</body>
				</html>
			</xsl:when>
			<xsl:when test="sbnode/@mode='tree_root'">
				<ul class="tree">
					<xsl:call-template name="menuentry"/>
				</ul>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="menuentry"/>
			</xsl:otherwise>
		</xsl:choose>
		
	</xsl:template>
	
	<xsl:template name="menuentry">
		<xsl:for-each select="*">
		<li id="entry_{@nodeid}">
			<xsl:choose>
				<xsl:when test="@mode='tree_root'"></xsl:when>
				<xsl:when test="@subnodes = 0">
					<img src="modules/sb_system/themes/_admin/images_sysicons/blank.gif" alt="" />
				</xsl:when>
				<xsl:otherwise>
					<a>
						<xsl:choose>
						<xsl:when test="count(./*) > 0">
							<!--<xsl:attribute name="href"><xsl:value-of select="$base_url"/>close=<xsl:value-of select="@id" /></xsl:attribute>-->
							<xsl:attribute name="href">backend.view=menu&amp;close=<xsl:value-of select="@nodeid" /></xsl:attribute>
							<img src="modules/sb_system/themes/_admin/images_sysicons/tree_close.gif" alt="close" />
						</xsl:when>
						<xsl:otherwise>
							<!--<xsl:attribute name="href"><xsl:value-of select="$base_url"/>open=<xsl:value-of select="@id" /></xsl:attribute>-->
							<xsl:attribute name="href">backend.view=menu&amp;open=<xsl:value-of select="@nodeid" /></xsl:attribute>
							<img src="modules/sb_system/themes/_admin/images_sysicons/tree_open.gif" alt="open" />
						</xsl:otherwise>
						</xsl:choose>
					</a>
				</xsl:otherwise>
			</xsl:choose>
			<!-- item link -->
			<xsl:choose>
				<xsl:when test="@type='root'">
					<a href="backend.view=info" class="type core" target="main"><xsl:value-of select="$locale/system/general/menu/root" /></a>
				</xsl:when>
				<xsl:otherwise>
					<a target="main">
						<xsl:attribute name="class">type <xsl:value-of select="@type" /></xsl:attribute>
						<xsl:if test="@custom_icon">
							<xsl:attribute name="style">background-image: url(../<xsl:value-of select="@custom_icon" />);</xsl:attribute>
						</xsl:if>
						<xsl:attribute name="href">backend.nodeid=<xsl:value-of select="@nodeid" /></xsl:attribute>
						<xsl:value-of select="@name" />
					</a>
				</xsl:otherwise>
			</xsl:choose>
			<!-- recurse -->
			<xsl:if test="*">
				<ul>
					<xsl:call-template name="menuentry" />
				</ul>
			</xsl:if>
		</li>
		</xsl:for-each>
	</xsl:template>

</xsl:stylesheet>