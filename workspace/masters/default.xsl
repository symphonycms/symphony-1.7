<?xml version="1.0" encoding="UTF-8" ?>

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
				<title><xsl:call-template name="page-title"/></title>
				<link rel="icon" type="image/gif" href="{$workspace}/img/favicon.gif" />
				<link rel="stylesheet" type="text/css" media="screen" href="{$workspace}/css/styles.css"/>
				<link rel="alternate" type="application/rss+xml" href="/rss/" />
			</head>

			<body id="{$current-page}-page">
				<div id="package">
					<h1 id="logo"><a href="{$root}"><xsl:value-of select="$website-name"/></a></h1>
					<xsl:call-template name="navigation"/>

					<xsl:apply-templates/>

					<ul id="footer">
						<li>Orchestrated by <a class="symphony" href="http://symphony21.com/">Symphony</a></li>
						<li>Fed through <a class="rss" href="{$root}/rss/">Journal</a></li>
					</ul>
				</div>
			</body>
		</html>	 
	</xsl:template>
</xsl:stylesheet>