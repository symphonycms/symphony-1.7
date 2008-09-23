<xsl:template match="entry" mode="brief">
	<div class="article-header">
		<p class="date">
			<xsl:call-template name="get-month">
				<xsl:with-param name="date" select="date"/>
			</xsl:call-template>
			<xsl:text> </xsl:text>
			<span>
				<xsl:call-template name="get-day">
					<xsl:with-param name="date" select="date"/>
					<xsl:with-param name="format" select=" 'short' "/>
				</xsl:call-template>
			</span>
		</p>

		<h3>
			<a href="{$root}/entries/{@handle}/">	<xsl:value-of select="fields/title"/></a>
			<xsl:if test="/data/events/user[@logged-in = 'true']">				<a href="{$root}/symphony/?page=/publish/section/edit/&amp;_sid={../@section-id}&amp;id={@id}">
					<img class="edit" src="{$workspace}/img/icon-edit.gif"/>
				</a>
			</xsl:if>
		</h3>
		<p class="filed-under">Filed Under: <xsl:apply-templates select="fields/categories/item"/></p>
	</div>
	<div class="article-body">
		<xsl:copy-of select="fields/body/*"/>
	</div>
	<p class="more-info">
		<a href="{$root}/entries/{@handle}/">Read More</a>
		<xsl:text>. </xsl:text>
		<a href="{$root}/entries/{@handle}/#comments">
			<xsl:choose>
				<xsl:when test="comments/@count = 0">No comments made</xsl:when>
				<xsl:when test="comments/@count = 1">1 comment</xsl:when>
				<xsl:otherwise><xsl:value-of select="comments/@count"/> comments</xsl:otherwise>
			</xsl:choose>
		</a>
		<xsl:text>. </xsl:text>
	</p>
</xsl:template>

<xsl:template match="entry" mode="full">
	<div class="article-header">
		<p class="date">
			<xsl:call-template name="get-month">
				<xsl:with-param name="date" select="date"/>
			</xsl:call-template>
			<xsl:text> </xsl:text>
			<span>
				<xsl:call-template name="get-day">
					<xsl:with-param name="date" select="date"/>
					<xsl:with-param name="format" select=" 'short' "/>
				</xsl:call-template>
			</span>
		</p>
		<h3>
			<a href="{$root}/entries/{@handle}/"><xsl:value-of select="fields/title"/></a>
			<xsl:if test="/data/events/user[@logged-in = 'true']">				<a href="{$root}/symphony/?page=/publish/section/edit/&amp;_sid={../@section-id}&amp;id={@id}">
					<img class="edit" src="{$workspace}/img/icon-edit.gif"/>
				</a>
			</xsl:if>
		</h3>
		<p class="filed-under">Filed Under: <xsl:apply-templates select="fields/categories/item"/></p>
	</div>
	<div class="article-body">
		<xsl:copy-of select="fields/body/*"/>
		<xsl:if test="fields/photo/item">
			<div id="image-block">
				<xsl:apply-templates select="fields/photo/item"/>
			</div>
		</xsl:if>
		<xsl:copy-of select="fields/more/*"/>
	</div>
	<p class="more-info">
		<em>
			<xsl:text>Total Number of Words: </xsl:text>
			<xsl:choose>
				<xsl:when test="fields/more">
					<xsl:value-of select="fields/body/@word-count + fields/more/@word-count"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="fields/body/@word-count"/>
				</xsl:otherwise>
			</xsl:choose>
		</em>
	</p>
</xsl:template>

<xsl:template match="fields/photo/item">
	<xsl:param name="path" select="substring-after(path,'workspace/')"/>
	<a href="{$root}/image/{$path}"><img src="{$root}/image/85/0/1/fff/{$path}" alt="Image Associated to {../../title}"/></a>
</xsl:template>

<xsl:template match="categories/item">
	<xsl:value-of select="."/>
	<xsl:if test="position() != last()">, </xsl:if>
</xsl:template>