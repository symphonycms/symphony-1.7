<xsl:template match="data">
	<!--
		If the URL Schema /year is null, give $this-year the current year.
		Otherwise, grab the URL Schema's year. Do the same with month.
	-->
	<xsl:param name="this-year">
		<xsl:choose>
			<xsl:when test="$year = '' "><xsl:value-of select="substring($today,1,4)"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="$year"/></xsl:otherwise>
		</xsl:choose>
	</xsl:param>
	<xsl:param name="this-month">
		<xsl:choose>
			<xsl:when test="$month = '' "><xsl:value-of select="substring($today,6,2)"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="$month"/></xsl:otherwise>
		</xsl:choose>
	</xsl:param>

	<h2>Past Entries</h2>
	<div id="archive">
		<ul id="year">
			<!--
				Send it to the "archive-year-list". Feed it the range of years to display
				and the current year.
			-->
			<xsl:call-template name="archive-year-list">
				<xsl:with-param name="year-start" select="archive-overview/@year-start"/>
				<xsl:with-param name="year-end" select="archive-overview/@year-end"/>
				<xsl:with-param name="this-year" select="$this-year"/>
			</xsl:call-template>
		</ul>
		<ul id="month">
			<xsl:apply-templates select="archive-overview/year[@value=$this-year]/month">
				<xsl:with-param name="this-month" select="$this-month"/>
			</xsl:apply-templates>
		</ul>
		<ul id="past-entries">
			<!-- If the list returns no results or that the entries aren't in the right month, show a graceful message -->
			<xsl:choose>
				<xsl:when test="archive-entry-list/error or (number(archive-entry-list/year/month/@value) != number($this-month))">
					<li>
						<xsl:text>There are no entries made in </xsl:text>
						<xsl:call-template name="get-month">
							<xsl:with-param name="month" select="$this-month"/>
						</xsl:call-template>
					</li>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="archive-entry-list/year/month/day/entry"/>
				</xsl:otherwise>
			</xsl:choose>
		</ul>
	</div>
</xsl:template>

<xsl:template name="archive-year-list">
	<xsl:param name="year-start"/>
	<xsl:param name="year-end"/>
	<xsl:param name="this-year"/>
	<xsl:param name="count" select="$year-end - $year-start"/>
	<xsl:param name="context-year" select="$year-end"/>

	<xsl:param name="is-active">
		<xsl:choose>
			<xsl:when test="$context-year = $this-year">active</xsl:when>
			<xsl:otherwise>not-active</xsl:otherwise>
		</xsl:choose>
	</xsl:param>

	<xsl:if test="$count &gt;= 0">
		<li><a class="{$is-active}" href="{$root}/archive/{$context-year}/"><xsl:value-of select="$context-year"/></a></li>
		<xsl:call-template name="archive-year-list">
			<xsl:with-param name="count" select="$count - 1"/>
			<xsl:with-param name="context-year" select="$context-year - 1"/>
			<xsl:with-param name="this-year" select="$this-year"/>
		</xsl:call-template>
	</xsl:if>
</xsl:template>

<xsl:template match="month">
	<xsl:param name="this-month"/>

	<xsl:param name="is-active">
		<xsl:choose>
			<xsl:when test="@value = $this-month">active</xsl:when>
			<xsl:otherwise>not-active</xsl:otherwise>
		</xsl:choose>
	</xsl:param>

	<li>
		<a class="{$is-active}" href="{$root}/archive/{../@value}/{@value}/">
			<xsl:call-template name="get-month">
				<xsl:with-param name="month" select="@value"/>
			</xsl:call-template>
			<xsl:text> </xsl:text>
			<small>(<xsl:value-of select="@entry-count"/>)</small>
		</a>
	</li>
</xsl:template>

<xsl:template match="archive-entry-list/year/month/day/entry">
	<li>
		<a href="{$root}/entries/{@handle}/"><xsl:value-of select="fields/title"/></a>
	</li>
</xsl:template>