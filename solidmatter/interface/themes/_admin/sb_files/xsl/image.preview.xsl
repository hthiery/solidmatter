<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	version="1.0" 
	exclude-result-prefixes="html" 
	xmlns:html="http://www.w3.org/1999/xhtml">

	<xsl:import href="global.default.xsl" />
	<xsl:import href="global.sbform.xsl" />
	
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
			<xsl:apply-templates select="response/content/sbnode" />
		</div>
	</body>
	</html>
	</xsl:template>
	
	<xsl:template match="sbnode">
		<div style="overflow:scroll; height:90%; width:100%;">
			<img src="/{@uuid}/preview/output" />
		</div>
		<!--<iframe height="450" width="650" src="backend.nodeid={@uuid}&amp;view=preview&amp;action=outputresized">
			
		</iframe>-->
	</xsl:template>

</xsl:stylesheet>