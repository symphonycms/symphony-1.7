<xsl:template name="page-title">
	<xsl:value-of select="$website-name"/>
	<xsl:text> - </xsl:text>
	<xsl:value-of select="$page-title" disable-output-escaping="yes" />
	<xsl:if test="$current-page = 'entries'">
		<xsl:if test="$entry = not('')">
			<xsl:text> - </xsl:text>
			<xsl:value-of select="data/entries/entry/fields/title"/>
		</xsl:if>
	</xsl:if>
</xsl:template>