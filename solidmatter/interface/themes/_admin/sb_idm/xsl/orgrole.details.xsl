<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	version="1.0" 
	exclude-result-prefixes="html" 
	xmlns:html="http://www.w3.org/1999/xhtml">

	<xsl:import href="../../sb_system/xsl/global.views.xsl" />
	<xsl:import href="../../sb_system/xsl/global.default.xsl" />

	<xsl:key name="unique_persons" match="sbnode[@nodetype='sbIdM:Person']" use="@uuid"/>
	
	<xsl:output 
		method="html"
		encoding="UTF-8"
		standalone="yes"
		indent="no"
	/>

	<xsl:template match="/">
	<html>
	<head>
		<xsl:apply-templates select="/response/metadata" />
	</head>
	<body>
		<xsl:call-template name="views" />
		<div class="workbench">
			<xsl:apply-templates select="response/errors" />
			<xsl:apply-templates select="$content/sbnode[@master]" />
		</div>
	</body>
	</html>
	</xsl:template>
		
	<xsl:template match="sbnode">
		
		<table class="default" width="100%" id="list">
			<thead>
				<tr><th colspan="6" >OrgRoles</th></tr>
				<tr class="th2">
					<th width="33%"><xsl:value-of select="$locale/sbSystem/labels/name" /></th>
					<th width="33%">TechRoles</th>
					<th width="33%">Type</th>
					<th width="1%"><xsl:value-of select="$locale/sbSystem/labels/options" /></th>
				</tr>
			</thead>
			<tbody>
				<xsl:if test="$content/OrgRoles/nodes/sbnode or $content/InheritedOrgRoles/nodes/sbnode">
					<xsl:for-each select="$master | $content/OrgRoles/nodes/sbnode[@nodetype='sbIdM:OrgRole'] | $content/InheritedOrgRoles/nodes/sbnode[@nodetype='sbIdM:OrgRole']">
						<tr>
							<xsl:call-template name="colorize" />
							<td>
								<a class="highlighted" href="/{@uuid}/details"><span class="type {@displaytype}"><xsl:value-of select="@label" /></span></a>
							</td>
							<td>
								<xsl:call-template name="render_techroles" />
							</td>
							<td>
								<xsl:choose>
									<xsl:when test="name(../..) = 'OrgRoles'">Direct</xsl:when>
									<xsl:when test="name(../..) = 'InheritedOrgRoles'">Inherited</xsl:when>
									<xsl:otherwise>Self</xsl:otherwise>
								</xsl:choose>
							</td>
							<td>
								
							</td>
						</tr>
					</xsl:for-each>
				</xsl:if>
				<!-- <xsl:otherwise>
					<tr><td colspan="6"><xsl:value-of select="$locale/sbSystem/texts/no_subobjects" /></td></tr>
				</xsl:otherwise> -->
			</tbody>
			<tfoot><tr><td colspan="4"></td></tr></tfoot>
		</table>
	
		<table class="default" width="100%" id="list">
			<thead>
				<tr>
					<th colspan="1" >Persons (<xsl:value-of select="count(//*[generate-id(.)=generate-id(key('unique_persons', ./@uuid)[1])])" />)</th>
					<th colspan="2" style="text-align:right;">
						<a href="/{@uuid}/details/print?mode=persons" target="_blank">Print Version (Persons)</a>
						 / 
						<a href="/{@uuid}/details/print?mode=orgroles" target="_blank">Print Version (OrgRoles)</a>
					</th>
				</tr>
				<tr class="th2">
					<th width="33%"><xsl:value-of select="$locale/sbSystem/labels/name" /></th>
					<th width="66%">TechRoles</th>
					<th width="1%"><xsl:value-of select="$locale/sbSystem/labels/options" /></th>
				</tr>
			</thead>
			<tbody>
			<xsl:choose>
				<xsl:when test="children[@mode='debug']//sbnode[@nodetype='sbIdM:Person']">
					<!-- <xsl:for-each select="children[@mode='debug']//sbnode[@nodetype='sbIdM:Person']"> -->
						<xsl:for-each select="//*[generate-id(.)=generate-id(key('unique_persons', ./@uuid)[1])]">
						<tr>
							<xsl:call-template name="colorize" />
							<td>
								<a class="highlighted" href="/{@uuid}/details"><span class="type {@displaytype}"><xsl:value-of select="@label" /></span></a>
							</td>
							<td>
								<xsl:call-template name="render_techroles" />
							</td>
							<td>
								
							</td>
						</tr>
					</xsl:for-each>
				</xsl:when>
				<xsl:otherwise>
					<tr><td colspan="6"><xsl:value-of select="$locale/sbSystem/texts/no_subobjects" /></td></tr>
				</xsl:otherwise>
			</xsl:choose>
			</tbody>
			<tfoot><tr><td colspan="3"></td></tr></tfoot>
		</table>
		
	</xsl:template>
	
	<xsl:template name="render_techroles">
		<xsl:if test="children[@mode='gatherTechRoles']/sbnode">
			<xsl:for-each select="children[@mode='gatherTechRoles']/sbnode">
				<a href="/{@uuid}"><span class="type {@displaytype}"><xsl:value-of select="@label" /> [<xsl:value-of select="@name" />]</span></a><br />
			</xsl:for-each>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>