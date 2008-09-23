<xsl:template match="data">
	<h2>Entry</h2>

	<xsl:apply-templates select="entries/entry" mode="full"/>

	<xsl:if test="entries/error">
		<p>No entry were found. Please check that the URL is correct.</p>
	</xsl:if>

	<xsl:choose>
		<xsl:when test="entries/entry/comments/@count = 1">
			<h2 id="comments"><xsl:value-of select="entries/entry/comments/@count"/> Comment</h2>
			<xsl:apply-templates select="comments/entry/comment"/>
		</xsl:when>
		<xsl:when test="entries/entry/comments/@count &gt; 1">
			<h2 id="comments"><xsl:value-of select="entries/entry/comments/@count"/> Comments</h2>
			<xsl:apply-templates select="comments/entry/comment"/>
		</xsl:when>
		<xsl:otherwise>
			<h2 id="comments">Comments</h2>
			<p>No comments have been made.</p>
		</xsl:otherwise>
	</xsl:choose>

	<h2 id="post-comment">Post a comment</h2>

	<div class="block">
		<div id="guideline">
			<h4>Comment Guidelines</h4>
			<ul>
				<li>Have no more than 2 links, otherwise your comment will be flagged as spam.</li>
				<li>Links are automagically generated.</li>
				<li>&lt;em&gt;text&lt;/em&gt; to make text <em>italic</em>.</li>
				<li>&lt;strong&gt;text&lt;/strong&gt; to make text <strong>bold</strong>.</li>
			</ul>
		</div>
		<xsl:call-template name="comment-form"/>
	</div>
</xsl:template>