<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml" encoding="UTF-8" indent="yes" />

<xsl:template match="data">
	<rss version="2.0">
		<channel>
			<title><xsl:value-of select="$website-name"/></title>
			<link><xsl:value-of select="$root"/></link>
			<description><xsl:value-of select="$website-name"/> Feed</description>
			<language>en-us</language>
			<generator>Symphony (build <xsl:value-of select="$symphony-build"/>)</generator>
			<xsl:for-each select="rss/entry">
				<item>
					<title><xsl:value-of select="fields/title"/></title>
					<link><xsl:value-of select="$root"/>/entries/<xsl:value-of select="@handle"/>/</link>
					<pubDate><xsl:value-of select="rfc822-date"/></pubDate>
					<guid><xsl:value-of select="$root"/>/entries/<xsl:value-of select="@handle"/>/</guid>
					<description><xsl:value-of select="fields/body"/></description>
				</item>
			</xsl:for-each>
		</channel>
	</rss>
</xsl:template>

</xsl:stylesheet>