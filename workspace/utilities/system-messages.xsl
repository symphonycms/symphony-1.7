<xsl:template match="post-comment | send-email">
	<xsl:if test="@sent='true'">
		<xsl:for-each select="notice">
			<p class="success"><xsl:value-of select="."/></p>
		</xsl:for-each>
	</xsl:if>
	<xsl:if test="@sent='false'">
		<xsl:for-each select="self::node()[@spam='true']/notice">
			<p class="error"><xsl:value-of select="."/></p>
		</xsl:for-each>
		<xsl:for-each select="missing/input">
			<p class="error">Missing Field: <xsl:value-of select="@name"/></p>
		</xsl:for-each>
		<xsl:for-each select="invalid/input">
			<p class="error">Invalid Field: <xsl:value-of select="@name"/></p>
		</xsl:for-each>
	</xsl:if>
</xsl:template>