<xsl:template name="contact-email-form">
	<xsl:param name="owner"/>
	<form action="" method="post">
		<fieldset>
			<xsl:apply-templates select="events/send-email" />
            <label>Your Name <input name="name" value="{//cookie/name}"/></label>
            <label>Your Email <input name="email" value="{//cookie/email}"/></label>
            <label>Subject<input name="subject" value="{//cookie/subject}"/></label>
            <label>Message<textarea name="message" rows="5" cols="21"><xsl:value-of select="//cookie/message"/></textarea></label>
			<input type="hidden" name="recipient-username" value="{$owner}" />
			<input id="submit" type="submit" name="action[send-email]" value="Send Message" />
		</fieldset>
	</form>
</xsl:template>