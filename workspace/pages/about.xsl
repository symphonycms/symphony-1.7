<xsl:template match="data">
	<h2>About Me</h2>

	<img src="{$root}/image/img/mug.gif" alt="My famous mug"/>

	<p>This is the website of <xsl:value-of select="concat(owner/author/first-name, ' ', owner/author/last-name)"/>, which is running off a freshly-installed copy of <a href="http://symphony21.com/">Symphony</a>. Things around here are still quite new, so you might like to check back in a few days.</p>

	<h2 id="contact-form">Contact Me</h2>

	<div class="block">
		<div id="guideline">
			<h4>Contact Form Notes</h4>
			<ul>
				<li>It's preferred to use the contact form rather than email. There is always a chance for emails to be picked up as spam.</li>
				<li>I will generally reply to messages within 24 hours unless I am horribly swamped.</li>
			</ul>
		</div>

		<xsl:call-template name="contact-email-form">
			<xsl:with-param name="owner" select="owner/author/@username"/>
		</xsl:call-template>
	</div>
</xsl:template>