<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml" 
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" 
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8" 
	indent="yes" />

<xsl:template match="/">
	<html>
		<head>
			<title><xsl:value-of select="$website-name"/> - <xsl:value-of select="$page-title"/></title>
			<link rel="stylesheet" type="text/css" media="screen" href="{$workspace}/css/maintenance.css"/>
			<link rel="icon" type="image/gif" href="{$root}/image/favicon.gif" />
		</head>

		<body>
			<div id="package">
				<h1 id="logo"><xsl:value-of select="$website-name"/></h1>
				<p>This site is currently in maintenance. Please check back at a later date.</p>
			</div>
		</body>
	</html>	 
</xsl:template>
</xsl:stylesheet>