<xsl:template name="navigation">
	<ul id="menu">
		<xsl:apply-templates select="data/navigation/page"/>
	</ul>
</xsl:template>

<xsl:template match="data/navigation/page">
	<xsl:variable name="nav-link">
		<xsl:choose>
			<xsl:when test="@type = 'index'"><xsl:value-of select="$root"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="concat($root, '/', @handle, '/')"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<li><a href="{$nav-link}"><xsl:value-of select="title"/></a></li>
</xsl:template>