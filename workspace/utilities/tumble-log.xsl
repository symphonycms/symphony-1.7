<xsl:template match="tumblelog/year/month/day">
	<dl class="listing">
		<dt>
			<xsl:call-template name="get-date">
				<xsl:with-param name="year" select="../../@value"/>
				<xsl:with-param name="month" select="../@value"/>
				<xsl:with-param name="day" select="@value"/>
				<xsl:with-param name="format" select=" 'short' "/>
			</xsl:call-template>
		</dt>
		<xsl:apply-templates select="entry"/>	
	</dl>
</xsl:template>

<xsl:template match="tumblelog/year/month/day/entry">
	<dd>
		<xsl:copy-of select="fields/log-entry/*"/>
	</dd>
</xsl:template>