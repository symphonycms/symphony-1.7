<xsl:template match="comments/entry/comment">
	<dl class="comment">
		<dt>
			<xsl:call-template name="get-comment-author"/>
			<xsl:text> </xsl:text>
			<xsl:if test="/data/events/user[@logged-in = 'true']">
				<a href="{$root}/symphony/?page=/publish/comments/edit/&amp;id={@id}">
					<img class="edit" src="{$workspace}/img/icon-edit.gif"/>
				</a>
			</xsl:if>
			<small>
				<xsl:call-template name="get-date">
					<xsl:with-param name="date" select="date"/>
					<xsl:with-param name="format" select=" 'short' "/>
				</xsl:call-template>

				<xsl:text> at </xsl:text>

				<xsl:call-template name="get-time">
					<xsl:with-param name="time" select="time"/>
				</xsl:call-template>
			</small>
		</dt>
		<dd><xsl:copy-of select="message/*"/></dd>
	</dl>
</xsl:template>

<xsl:template name="get-comment-author">
	<xsl:choose>
		<xsl:when test="url/node()">
			<a href="{url}"><xsl:value-of select="author"/></a>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="author"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template name="get-comment-message">
	<xsl:copy-of select="message/*"/>
</xsl:template>

<xsl:template name="comment-form">
	<form action="" method="post">
		<fieldset>
			<xsl:apply-templates select="events/post-comment"/>
			<label>Name <input name="name" value="{//cookie/name}"/></label>
			<label>Email <small>Required but never displayed</small> <input name="email" value="{//cookie/email}"/></label>
			<label>Website <small>http://</small> <input name="website" value="{//cookie/url}"/></label>
			<label>Comment <textarea name="comment" rows="12" cols="24"></textarea></label>
			<input type="hidden" name="entry-handle" value="{$entry}" />
			<input type="hidden" name="section" value="entries" />
			<input type="hidden" name="remember" value="on" />
			<input id="submit" name="action[comment]" type="submit" value="Post Comment" />
		</fieldset>
	</form>
</xsl:template>