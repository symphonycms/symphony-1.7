/***
 *
 * Symphony web publishing system
 *
 * Copyright 2004–2006 Twenty One Degrees Pty. Ltd.
 *
 * @version 1.7
 * @licence https://github.com/symphonycms/symphony-1.7/blob/master/LICENCE
 *
 ***/

function CodeEditor() {
	var textarea = document.getElementById("code-editor");

	if (!textarea) return;

	textarea.addEventListener("keypress", CodeEditor.handleKeyPress, false);
};

// Trigger keys

CodeEditor.keys = {
	9: function(pre, post) { // Tabs
		this.value = pre + "\t" + post;

		return 0;
	},
	13: function(pre, post) { // Return (auto-indent new lines)
		var indent = pre.split("\n").pop().match(/^\s*/) || "";

		this.value = pre + "\n" + indent + post;

		return 0;
	},
	61: function(pre, post) { // Attributes
		if (/<[^>=]+(="[^"]*"[^>=]+)*$/.test(pre)) {
			this.value = pre + "=\"\"" + post;

			return -1;
		}

		this.value = pre + "=" + post;

		return 0;
	},
	62: function(pre, post) { // Closing tags
		var nodeData = pre.split("<").pop().match(/^[^?!\s/>]+(?!.*?>)(?!.*?\/$)/);

		if (!nodeData) {
			this.value = pre + ">" + post;

			return 0;
		}

		this.value = pre + "></" + nodeData + ">" + post;

		return pre.length + post.length - this.value.length + 1;
	}
};

CodeEditor.handleKeyPress = function(event) {
	var handler = CodeEditor.keys[event.which || event.keyCode];

	if (!handler || event.metaKey) return;

	event.preventDefault();

	var scrollTop = this.scrollTop;

	var pre = this.value.substr(0, this.selectionStart),
		post = this.value.substr(this.selectionEnd);

	var position = handler.call(this, pre, post) + this.value.length - post.length;

	this.setSelectionRange(position, position);

	if (this.scrollTop != scrollTop) this.scrollTop = scrollTop;
};

window.addEventListener("load", CodeEditor, false);
