<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml" 
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" 
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8" 
	indent="yes" />

<xsl:template match="/">
<html>
	<head>
		<title>Email this Article</title>
		<link rel="stylesheet" type="text/css" media="screen" href="{$root}/workspace/css/forward-styles.css" />
                <script type="text/javascript" src="{$root}/workspace/js/email-friend.js"></script>
	</head>

	<body>
		<form id="email" action="" method="post">
			<h1>Email this Article</h1>
			<h2><xsl:value-of select="/data/article/headline-1/" /></h2>
    <xsl:choose>
        <xsl:when test="/data/events/email-article/@sent = 'true'">
                        <p>Email successfully sent. Click <a href="">here to close</a></p>
        </xsl:when>
        <xsl:otherwise>

                    <xsl:if test="/data/events/email-article/@sent = 'false'">
			<p><strong>An error occured while attempting to send. Please check you filled each field out.</strong></p>
                    </xsl:if>

			<p>Please fill out the form below to e-mail this article to a friend.</p>

			<fieldset>
				<label>Recipient e-mail address <input name="recipient_email" /></label>
				<label>Your name <input name="your_name" /></label>
				<label>Your email address <input name="your_email" /></label>
				<label>Accompanying message <textarea name="message" rows="8" cols="40"></textarea></label>
                                <input type="hidden" name="article" value="{/data/article/@handle}" />
				<input name="action[send]" type="submit" value="Send" />
			</fieldset>
        </xsl:otherwise>
    </xsl:choose>
		</form>
	</body>
</html>

</xsl:template>

</xsl:stylesheet>