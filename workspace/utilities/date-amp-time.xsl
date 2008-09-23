<!-- This utility uses some advanced XSLT concepts. We recommend not to modify the code unless you are familiar with XSLT -->

<xsl:template name="get-date" match="date">
	<xsl:param name="date" select="."/>
	<xsl:param name="year" select="substring($date,1,4)"/>
	<xsl:param name="month" select="substring($date,6,2)"/>
	<xsl:param name="day" select="substring($date,9,2)"/>
	<xsl:param name="format" select=" 'long' "/>

	<xsl:call-template name="get-day">
		<xsl:with-param name="day" select="$day"/>
		<xsl:with-param name="format" select="$format"/>
	</xsl:call-template>

	<xsl:text> </xsl:text>

	<xsl:call-template name="get-month">
		<xsl:with-param name="month" select="$month"/>
		<xsl:with-param name="format" select="$format"/>
	</xsl:call-template>

	<xsl:text> </xsl:text>

	<xsl:call-template name="get-year">
		<xsl:with-param name="year" select="$year"/>
		<xsl:with-param name="format" select="$format"/>
	</xsl:call-template>
</xsl:template>

<xsl:template name="get-month">
	<xsl:param name="date"/>
	<xsl:param name="month" select="substring($date,6,2)"/>
	<xsl:param name="format" select="'long'"/>
	<xsl:variable name="this-month" select="format-number($month,'0')"/>

	<xsl:choose>
		<xsl:when test="$this-month = 1 and $format = 'short'">Jan</xsl:when>
		<xsl:when test="$this-month = 2 and $format = 'short'">Feb</xsl:when>
		<xsl:when test="$this-month = 3 and $format = 'short'">Mar</xsl:when>
		<xsl:when test="$this-month = 4 and $format = 'short'">Apr</xsl:when>
		<xsl:when test="$this-month = 5 and $format = 'short'">May</xsl:when>
		<xsl:when test="$this-month = 6 and $format = 'short'">Jun</xsl:when>
		<xsl:when test="$this-month = 7 and $format = 'short'">Jul</xsl:when>
		<xsl:when test="$this-month = 8 and $format = 'short'">Aug</xsl:when>
		<xsl:when test="$this-month = 9 and $format = 'short'">Sep</xsl:when>
		<xsl:when test="$this-month = 10 and $format = 'short'">Oct</xsl:when>
		<xsl:when test="$this-month = 11 and $format = 'short'">Nov</xsl:when>
		<xsl:when test="$this-month = 12 and $format = 'short'">Dec</xsl:when>
		<xsl:when test="$this-month = 1 and $format = 'long'">January</xsl:when>
		<xsl:when test="$this-month = 2 and $format = 'long'">February</xsl:when>
		<xsl:when test="$this-month = 3 and $format = 'long'">March</xsl:when>
		<xsl:when test="$this-month = 4 and $format = 'long'">April</xsl:when>
		<xsl:when test="$this-month = 5 and $format = 'long'">May</xsl:when>
		<xsl:when test="$this-month = 6 and $format = 'long'">June</xsl:when>
		<xsl:when test="$this-month = 7 and $format = 'long'">July</xsl:when>
		<xsl:when test="$this-month = 8 and $format = 'long'">August</xsl:when>
		<xsl:when test="$this-month = 9 and $format = 'long'">September</xsl:when>
		<xsl:when test="$this-month = 10 and $format = 'long'">October</xsl:when>
		<xsl:when test="$this-month = 11 and $format = 'long'">November</xsl:when>
		<xsl:when test="$this-month = 12 and $format = 'long'">December</xsl:when>
	</xsl:choose>
</xsl:template>

<xsl:template name="get-day">
	<xsl:param name="date"/>
	<xsl:param name="day" select="substring($date,9,2)"/>
	<xsl:param name="format" select="'long'"/>
	<xsl:variable name="this-day" select="format-number($day,'0')"/>

	<xsl:variable name="suffix">
		<xsl:choose>
			<xsl:when test="string-length($day) = 2 and starts-with($day, '1')">th</xsl:when>
			<xsl:when test="contains($day, '1')">st</xsl:when>
			<xsl:when test="contains($day, '2')">nd</xsl:when>
			<xsl:when test="contains($day, '3')">rd</xsl:when>
			<xsl:otherwise>th</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:choose>
		<xsl:when test="$format = 'long' ">
			<xsl:value-of select="$this-day"/><sup><xsl:value-of select="$suffix"/></sup>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$this-day"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template name="get-year">
	<xsl:param name="date"/>
	<xsl:param name="year" select="substring($date,1,4)"/>
	<xsl:param name="format" select="'long'"/>

	<xsl:choose>
		<xsl:when test="$format = 'long' ">
			<xsl:value-of select="$year"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="substring($year,3,2)"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template name="get-time" match="time">
	<xsl:param name="time" select="."/>
	<xsl:variable name="hour" select="format-number(substring-before($time, ':'), '0')"/>
	<xsl:variable name="minute" select="substring-after($time, ':')"/>

	<xsl:variable name="suffix">
		<xsl:choose>
			<xsl:when test="$hour &lt;= 12">am</xsl:when>
			<xsl:otherwise>pm</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:value-of select="concat(($hour mod 12), ':', $minute, $suffix)"/>
</xsl:template>