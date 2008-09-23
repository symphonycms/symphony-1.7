<xsl:template match="data">
	<h2>
		<xsl:text>Latest Entry </xsl:text>
		<xsl:if test="/data/events/user[@logged-in = 'true']">			<a href="{$root}/symphony/?page=/publish/section/new/&amp;_sid={homepage-entry/@section-id}">
				<img class="edit" src="{$workspace}/img/icon-edit.gif"/>
			</a>
		</xsl:if>
	</h2>
	<xsl:apply-templates select="homepage-entry/entry" mode="brief"/>
	<h2>Tumblelog <small><a href="http://en.wikipedia.org/wiki/Tumblelog">(?)</a></small></h2>
	<xsl:apply-templates select="tumblelog/year/month/day"/>
</xsl:template>