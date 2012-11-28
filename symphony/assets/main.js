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

// Class Framework

var Class = {
	create: function() {
		var instance = this.identify && this.identify.apply(this, arguments) || {};

		for (var property in this) {
			if (property in Class.reserved) continue;

			instance[property] = Class.bind(this[property], instance);
		}

		return this.create && this.create.apply(instance, arguments) || instance;
	},
	extend: function(template, parent) {
		if (parent)
			Class.define.call(template, parent, true);

		return {
			create: function() {
				return Class.create.apply(template, arguments);
			},
			extend: function(extension) {
				return Class.extend.call(this, extension, template);
			},
			define: Class.define
		};
	},
	reserved: { create: null, identify: null },
	bind: function(method, owner) {
		if (typeof method != "function") return method;

		return function() {
			return method.apply(owner, arguments);
		};
	},
	define: function(source, preserve) {
		for (var property in source) {
			if (preserve && this[property]) continue;

			this[property] = source[property];
		}

		return this;
	},
	patch: function(className, methodName, patch) {
		// Use Class.patch for overloading class instance methods. This is somewhat
		// of a nasty hack but it turns out to be very useful.

		var bind = Class.bind, temp = window[className].extend({
			create: new Function()
		});

		Class.bind = function(method) {
			return method;
		};

		var original = temp.create()[methodName], extension = {};

		Class.bind = bind;

		extension[methodName] = function() {
			original.apply(this, arguments);
			patch.apply(this, arguments);
		};

		window[className] = window[className].extend(extension);
	}
};


// Tasks (single class instances useful for once-off interactions with the document)

var Task = {
	create: function() {
		if (this.initialise)
			window.addEventListener("load", this.initialise, false);
	},
	define: Class.define,
	extend: function(template) {
		template.extend = function() {
			return Task.extend.apply(template, arguments);
		};

		Class.define.call(template, this, true);

		return Class.create.apply(template);
	}
};


// HTML Elements

var elementMethods = {
	insertElement: function(node, nextSibling) {
		var element = Element.create(node);

		return nextSibling ?
			this.insertBefore(element, nextSibling)
			: this.appendChild(element);
	},
	insertTextNode: function(text, nextSibling) {
		var textNode = document.createTextNode(text);

		return nextSibling ?
			this.insertBefore(textNode, nextSibling)
			: this.appendChild(textNode);
	},
	setAttributes: function() {
		for (var i = 0; i < arguments.length / 2; i++)
			this.setAttribute(arguments[2 * i], arguments[2 * i + 1]);
	},
	find: function(type, attribute, value) {
		return Elements.find(type, attribute, value, this);
	},
	replaceContents: function(node) {
		var range = document.createRange();

		range.selectNodeContents(this);
		range.deleteContents();

		if (node)
			return this.appendChild(Element.create(node));
	},
	previousElement: function() {
		var element = this.previousSibling;

		while (element) {
			if (element.nodeType == 1)
				return Element.create(element);

			element = element.previousSibling;
		}
	},
	nextElement: function() {
		var element = this.nextSibling;

		while (element) {
			if (element.nodeType == 1)
				return Element.create(element);

			element = element.nextSibling;
		}
	},
	insertAfter: function(node, sibling) {
		var element = Element.create(node),
			nextElement = sibling.nextElement ?
				sibling.nextElement()
				: Element.create(sibling).nextElement();

		return nextElement ?
			this.insertBefore(element, nextElement)
			: this.appendChild(element);
	},
	hasClass: function(className) {
		return this.className.match(new RegExp("(^|\\s)" + className + "(\\s|$)"));
	},
	addClass: function(className) {
		if (this.hasClass(className)) return;

		this.className = (this.className + " " + className).replace(/^\s/, "");
	},
	removeClass: function(className) {
		var matchClass = new RegExp("(^|\\s)" + className + "(\\s|$)", "g");

		this.className = this.className.replace(matchClass, " ").replace(/^\s|\s{2,}|\s$/g, "");
	},
	toggleClass: function(className, className2) {
		if (!className2) {
			return this.hasClass(className) ? this.removeClass(className) : this.addClass(className);
		}

		if (this.hasClass(className)) {
			this.removeClass(className);
			this.addClass(className2);

		} else if (this.hasClass(className2)) {
			this.removeClass(className2);
			this.addClass(className);
		}
	},
	getPosition: function() {
		var coordinates = { x: 0, y: 0 }, element = this;

		while (element.offsetParent) {
			coordinates.x += element.offsetLeft;
			coordinates.y += element.offsetTop;
			element = element.offsetParent;
		}

		return coordinates;
	}
};

var elementClass = Class.define.call({
	identify: function(node) {
		return node.appendChild ? node : document.createElement(node);
	}
}, elementMethods);

if (window.Element && Element.prototype) {
	Class.define.call(Element.prototype, elementMethods);

	Class.define.call(Element, {
		create: elementClass.identify,
		define: Class.define,
		extend: function(extension) {
			return Class.extend.call(this, extension, elementClass);
		}
	});

} else {
	var Element = Class.extend(elementClass);
};

var Elements = Element; // For semantics

Element.define({
	find: function(type, attribute, value, context) {
		var nodeList = (context || document).getElementsByTagName(type),
			elements = Element.List.create(null, nodeList);

		if (!attribute) return elements;

		elements.filter(function() {
			return value ?
				this.getAttribute(attribute) == value
				: attribute ? this.getAttribute(attribute) : true;
		});

		return elements;
	},
	match: function(expression, contextNode) {
		// Use XPath expressions to generate an array of matched nodes; currently this
		// only works in Firefox and latest Webkit builds.

		var result = document.evaluate(expression, contextNode || document.documentElement, null, XPathResult.ANY_TYPE, null);

		return Element.List.create(result.iterateNext, result);
	},
	createFragment: function() {
		return Class.define.call(document.createDocumentFragment(), elementMethods);
	}
});

Element.List = Class.extend({
	identify: function(generator, context) {
		var item, result = [];

		if (!generator) {
			var i = 0;

			generator = function() {
				return this[i++];
			};
		}

		while (item = generator.apply(context))
			result.push(Element.create(item));

		return result;
	},
	each: function(iterator) {
		var item, i = this.length;

		while (item = this[--i])
			iterator.call(item, i, this);

		return this;
	},
	filter: function(iterator) {
		var element, i = this.length;

		while (element = this[--i])
			this.splice(i, iterator.call(element, i, this) ? 0 : 1);
	},
	addEventListener: function(event, handler, capture) {
		this.each(function() {
			this.addEventListener(event, handler, capture);
		});
	}
});


// Event Methods

if (!window.Event)
	var Event = {};

Event.silence = function(event) {
	event.preventDefault();
	event.stopPropagation();
};


// Error Messages

function Error(message) {
	var oldError = document.getElementById("notice");

	if (oldError) oldError.parentNode.removeChild(oldError);

	var error = Element.create("p"), body = document.body;

	error.insertTextNode(message);
	error.id = "notice";
	error.className = "error";
	error.style.zIndex = 5;

	// Disgusting rendering bug
	var height = error.clientHeight + "px";
	error.style.marginTop = "-" + height;
	body.style.marginTop = height;

	body.insertBefore(error, body.firstChild);

	fixRedrawBug();
};


// Buttons (meant for JavaScript actions)

var Button = Class.extend({
	create: function(action, text) {
		this.insertTextNode(text);

		this.addEventListener("mousedown", Event.silence, false);
		this.addEventListener("click", Event.silence, false);

		this.addEventListener("click", action, false);
	},
	identify: function() {
		return Element.create("a");
	}
});


// Requests

var Request = Class.extend({
	create: function(resource, handler) {
		this.resource = resource;
		this.handleSuccess = handler;
	},
	get: function(data) {
		this.active = true;

		var request = new XMLHttpRequest();
		request.open("GET", this.resource + data, true);
		request.onreadystatechange = Request.prepareHandler(request, this);
		request.send(null);
	},
	post: function(data, contentType) {
		this.active = true;

		var request = new XMLHttpRequest();
		request.open("POST", this.resource, true);
		request.setRequestHeader("Content-Type", contentType || "application/x-www-form-urlencoded");
		request.onreadystatechange = Request.prepareHandler(request, this);
		request.send(data + "&cookie=" + encodeURIComponent(document.cookie));
	},
	active: false,
	handleError: function(statusCode, statusText) {
		// Overwrite this method to use a different error handler.

		if (this.active)
			Error("A request was not completed properly. Try reloading this page before making the request again.");
	}
});

Request.prepareHandler = function(request, instance) {
	window.addEventListener("unload", function() {
		request.abort();
		instance.active = false;
	}, false);

	return function() {
		if (request.readyState != 4) return;

		instance.active = false;

		if (request.status != 200 || !request.responseXML)
			instance.handleError(request.status, request.statusText);
		else instance.handleSuccess(request.responseXML);
	};
};


// Read Cookie

var Cookie = {
	get: function(key) {
		var cookie, cookies = document.cookie.split(";"), i = cookies.length;

		while (i--) {
			cookie = cookies[i];

			if (cookie.indexOf(key + "=") < 0) continue;

			return cookie.split("=").pop();
		}

		return null;
	}
};


// Simple Logarithmic Animation

var Animation = {
	run: function(update, origin, target) {
		origin += (target - origin) / 3;

		if (update(origin, target))
			setTimeout(Animation.run, 32, update, origin, target);
	}
};


// Date Calendar

var Calendar = Class.extend({
	create: function(date) {
		this.date = date ? new Date(date) : new Date();

		this.selectedDate = this.date.getDate();
		this.selectedMonth = ["January", "February", "March", "April", "May", "June", "July",
			"August", "September", "October", "November", "December"][this.date.getMonth()];
		this.selectedYear = this.date.getFullYear();

		this.setAttribute("class", "calendar");

		var caption = this.insertElement("caption");
		this.heading = caption.insertTextNode(this.selectedMonth + " " + this.selectedYear);

		var thead = this.insertElement("thead").insertElement("tr");

		var days = ["s", "m", "t", "w", "t", "f", "s"];

		for (var i = 0; i < days.length; i++)
			thead.insertElement("th").insertTextNode(days[i]);

		this.cache = {};
		this.tbody = this.insertElement("tbody");

		var back = Button.create(this.back, "Previous month");
		back.setAttribute("title", "Previous month");
		this.heading.parentNode.insertBefore(back, this.heading);

		var next = Button.create(this.next, "Next month");
		next.setAttribute("title", "Next month");
		this.heading.parentNode.insertBefore(next, this.heading);

		this.populate();
	},
	identify: function() {
		return Element.create("table");
	},
	populate: function() {
		var month = this.date.getMonth(),
			year = this.date.getFullYear(),

			offset = new Date(year, month).getDay(),
			amount = 32 - new Date(year, month, 32).getDate()

			months = ["January", "February", "March", "April", "May", "June", "July", "August",
				"September", "October", "November", "December"],

			data = months[month] + " " + year,
			tbody = this.cache[data];

		this.heading.data = data;

		if (tbody)
			return this.replaceChild(tbody, this.lastChild);

		tbody = this.cache[data] = Element.create("tbody");
		var rows = [], cells = [], links = [];

		for (var i = 0; i < (offset + amount) / 7; i++) {
			rows.push(tbody.insertElement("tr"));

			for (var j = 0; j < 7; j++) {
				var k = 7 * i + j;
				cells.push(rows[i].insertElement("td"));

				if (k >= offset && k < offset + amount) {
					links.push(cells[k].appendChild(Button.create(this.select,
						k - offset + 1)));

					if (months[month] == this.selectedMonth && year == this.selectedYear &&
							this.selectedDate == k - offset + 1) {
						if (this.selectedLink)
							this.selectedLink.removeClass("selected");

						this.selectedLink = links[k - offset];
						this.selectedLink.setAttribute("class", "selected");
					}
				}
			}
		}

		return this.replaceChild(tbody, this.lastChild);
	},
	select: function(event) {
		if (this.selectedLink)
			this.selectedLink.removeClass("selected");

		this.selectedLink = event.currentTarget;
		this.selectedLink.addClass("selected");

		var monthInfo = this.heading.data.split(" ");

		this.selectedDate = event.currentTarget.firstChild.data;
		this.selectedMonth = monthInfo[0];
		this.selectedYear = monthInfo[1];

		if (this.onchange)
			this.onchange();
	},
	next: function() {
		var month = this.date.getMonth(), year = this.date.getFullYear();

		this.date = (month < 11) ? new Date(year, month + 1) : new Date(year + 1, 0);

		this.populate();
	},
	back: function() {
		var month = this.date.getMonth(), year = this.date.getFullYear();

		this.date = (month > 0) ? new Date(year, month - 1) : new Date(year - 1, 11);

		this.populate();
	}
});

// Table Enhancements

var Selection = Task.extend({
	initialise: function() {
		this.withSelected = document.getElementsByName("with-selected")[0];

		var tbody = Elements.find("tbody").pop();

		if (!(tbody && Element.create(tbody.parentNode).hasClass("ordered") || this.withSelected)) return;

		var links = tbody.getElementsByTagName("a");

		tbody.addClass("able");

		this.rows = Element.List.create(null, tbody.getElementsByTagName("tr"));

		tbody.addEventListener("mousedown", Event.silence, false);
		tbody.addEventListener("dblclick", this.selectAll, false);

		for (var i = 0; i < this.rows.length; i++)
			this.rows[i].addEventListener("click", this.activate, false);

		for (var j = 0; j < links.length; j++)
			links[j].addEventListener("click", this.interrupt, false);
	},
	activate: function(event) {
		var row = event.currentTarget, checkbox = row.getElementsByTagName("input")[0];

		if (!checkbox) return;

		checkbox.checked = !row.hasClass("selected");

		row.toggleClass("selected");
	},
	selectAll: function() {
		for (var row, input, i = 0; row = this.rows[i]; i++) {
			input = row.getElementsByTagName("input")[0];

			if (!input) continue;

			row.addClass("selected");
			input.checked = true;
		}
	},
	interrupt: function(event) {
		event.stopPropagation();
	}
});

var Filter = Task.extend({
	initialise: function() {
		this.field = document.getElementsByName("filter")[0];

		if (!this.field) return;

		this.field.addEventListener("change", this.go, false);
	},
	go: function() {
		var component = "&filter=" + this.field.value;

		window.location = window.location.href.replace(/(&pg=[^&]+)|(&filter=[^&]+)/g, "") + component;
	}
});

var Reorder = Task.extend({
	initialise: function() {
		this.table = Elements.find("table").pop();
		if (!this.table || !this.table.hasClass("ordered")) return;

		this.tbody = this.table.find("tbody")[0];
		if (this.tbody.getElementsByTagName("tr").length < 2) return;

		var heading = document.getElementsByTagName("h2")[0];
		this.title = heading.firstChild.data.toLowerCase();

		this.button = Button.create(this.switchMode, "Reorder");
		this.button.setAttribute("class", "reorder button");
		this.button.setAttribute("title", "Reorder " + this.title);
		heading.appendChild(this.button);

		this.request = Request.create("ajax/?action=reorder", this.done);
	},
	switchMode: function() {
		var reordering = this.table.hasClass("ordered"), order = "";

		if (!reordering && this.request.active) return;

		this.tbody.find("tr").each(function(position) {
			if (reordering) {
				this.removeEventListener("click", Selection.activate, false);
				this.addEventListener("mousedown", Reorder.grab, false);

			} else {
				var checkbox = this.getElementsByTagName("input")[0];

				if (checkbox)
					order += "&" + checkbox.name + "=" + position;

				this.removeEventListener("mousedown", Reorder.grab, false);
				this.addEventListener("click", Selection.activate, false);
			}
		});

		if (reordering) {
			this.table.toggleClass("ordered", "reordering");
			this.tbody.removeEventListener("dblclick", Selection.selectAll, false);
			this.button.addClass("done");

		} else {
			var data = "handle=" + this.title.replace(/\s+/g, "") + order;
			this.request.post(data);
		}
	},
	done: function() {
		this.tbody.addEventListener("dblclick", Selection.selectAll, false);
		this.table.toggleClass("ordered", "reordering");
		this.button.removeClass("done");
	},
	grab: function(cursor) {
		this.activeRow = cursor.currentTarget;
		this.activeRow.addClass("active");

		cursor.preventDefault();

		var cell = this.activeRow.getElementsByTagName("td")[0];

		var target = cell;
		var targetY = 0;

		while (target.offsetParent) {
			targetY += target.offsetTop;
			target = target.offsetParent;
		}

		var cursorY = cursor.pageY;

		this.activeRow.top = targetY;
		this.activeRow.bottom = this.activeRow.top + cell.clientHeight - 1;

		window.addEventListener("mouseup", this.cancel, true);
		window.addEventListener("mousemove", this.move, true);
	},
	move: function(cursor) {
		if (cursor.pageY < this.activeRow.top) {
			var sibling = this.activeRow.previousElement();

			if (sibling) {
				this.activeRow.parentNode.insertBefore(this.activeRow, sibling);
				this.activeRow.bottom = this.activeRow.top - 1;
				this.activeRow.top = this.activeRow.top - sibling.getElementsByTagName("td")[0].clientHeight;
			}

		} else if (cursor.pageY > this.activeRow.bottom) {
			var sibling = this.activeRow.nextElement();

			if (sibling) {
				var parent = Element.create(this.activeRow.parentNode);
				parent.insertAfter(this.activeRow, sibling);

				this.activeRow.top = this.activeRow.bottom + 1;
				this.activeRow.bottom = this.activeRow.bottom +
					sibling.getElementsByTagName("td")[0].clientHeight;
			}
		}

		if (sibling) {
			if (sibling.hasClass("even")) {
				sibling.removeClass("even");
				this.activeRow.addClass("even");
			} else {
				this.activeRow.removeClass("even");
				sibling.addClass("even");
			}
		}
	},
	cancel: function() {
		this.activeRow.removeClass("active");

		window.removeEventListener("mousemove", this.move, true);
		window.removeEventListener("mouseup", this.cancel, true);
	}
});


// Form Enhancements

var CustomFields = Task.extend({
	initialise: function() {
		this.container = document.getElementById("context");
		this.selectBox = document.getElementsByName("fields[type]")[0];

		if (!this.container || !this.selectBox) return;

		this.required = document.getElementsByName("fields[required]")[0];
		this.location = document.getElementsByName("fields[location]")[0];

		this.title = document.getElementsByName("fields[name]")[0];
		this.originalName = this.title.value;

		if (document.getElementsByName("action[delete]")[0])
			document.forms[0].onsubmit = this.warnNameChange;

		var validator = document.getElementsByName("fields[validator]")[0];

		if (validator)
			validator.onchange = this.customValidation;

		this.options = ["input", "textarea", "select", "checkbox", "upload", "foreign"];

		for (var option, i = 0; option = this.options[i]; i++) {
			if (this.selectBox.options.length < i) break;

			if (this.selectBox.value != option) this[option + "Build"]();

			else this[option + "Fragment"] = document.createDocumentFragment();
		}

		this.selectBox.onchange = this.switcharoo;

		if (this.required)
			this.location.onchange = this.switchRequired;
	},
	switcharoo: function() {
		var range = document.createRange();
		range.selectNodeContents(this.container);

		for (var fragment, i = 0; fragment = this[this.options[i] + "Fragment"]; i++) {
			if (fragment.childNodes.length < 1)
				fragment.appendChild(range.extractContents());

			if (this.selectBox.value == this.options[i])
				this.container.appendChild(fragment);
		}

		if (this.required)
			this.switchRequired();
	},
	switchRequired: function() {
		this.required.disabled = (this.selectBox.value in { checkbox: null, upload: null } || this.location.value == "drawer");
	},
	inputBuild: function() {
		this.inputFragment = document.createDocumentFragment();

		var options = ["None", "Numeric", "Word Character", "Alphanumeric", "Email", "URL",
			"Date (yyyy-mm-dd)", "Time (hh:mm:ss)", "Date and Time (yyyy-mm-dd hh:mm:ss)"],

		validationRule = this.inputFragment.appendChild(Element.create("label"));
		validationRule.insertTextNode("Validation Rule");

		var selectBox = validationRule.insertElement("select");
		selectBox.setAttribute("name", "fields[validator]");

		for (var option, i = 0; i < options.length; i++)
			selectBox.options[i] = new Option(options[i], i);

		selectBox.options[selectBox.options.length] = new Option("Custom Rule...", "custom");

		selectBox.onchange = this.customValidation;

		var formatList = this.inputFragment.appendChild(Element.create("label")),

		formatCheckbox = formatList.insertElement("input");
		formatCheckbox.setAttribute("name", "fields[create_input_as_list]");
		formatCheckbox.setAttribute("type", "checkbox");

		formatList.insertTextNode(" Split by commas ");

		var applyFormatting = this.inputFragment.appendChild(Element.create("label"));
		applyFormatting.insertElement("input").setAttributes("name", "fields[format]", "type", "checkbox", "checked", "checked");
		applyFormatting.insertTextNode(" Apply formatting");

		var info = formatList.insertElement("small");
		info.insertTextNode("Validation will apply to each segment");
	},
	customValidation: function(event) {
		var validator = event.currentTarget.value, label = event.currentTarget.parentNode,
			customRuleInput = document.getElementsByName("fields[validation_rule]")[0];

		if (!this.validationFragment) {
			this.validationFragment = document.createDocumentFragment();

			if (!customRuleInput) {
				var customRule = this.validationFragment.appendChild(Element.create("label"));
				customRule.insertTextNode("Custom Validation Rule");

				customRuleInput = customRule.insertElement("input");
				customRuleInput.setAttribute("name", "fields[validation_rule]");
			}
		}

		if (this.validationFragment.childNodes.length > 0 && validator == "custom") {
			label.parentNode.insertBefore(this.validationFragment, label.nextSibling);

			var validationRule = document.getElementsByName("fields[validation_rule]")[0];
			validationRule.focus();
			validationRule.select();
		}

		if (this.validationFragment.childNodes.length < 1 && validator != "custom")
			this.validationFragment.appendChild(customRuleInput.parentNode);
	},
	textareaBuild: function() {
		this.textareaFragment = document.createDocumentFragment();

		var label = this.textareaFragment.appendChild(Element.create("label"));
		label.insertTextNode("Make textarea ");

		var input = label.insertElement("input");
		input.setAttribute("name", "fields[size]");
		input.setAttribute("value", "25");
		input.setAttribute("size", 2);
		input.setAttribute("maxlength", 2);

		label.insertTextNode(" rows tall.");

		var applyFormatting = this.textareaFragment.appendChild(Element.create("label"));
		applyFormatting.insertElement("input").setAttributes("name", "fields[format]", "type", "checkbox", "checked", "checked");
		applyFormatting.insertTextNode(" Apply formatting");
	},
	selectBuild: function() {
		this.selectFragment = document.createDocumentFragment();

		var options = this.selectFragment.appendChild(Element.create("label"));
		options.insertTextNode("Options ");

		var small = options.insertElement("small");
		small.insertTextNode("Separate by commas ");

		var input = options.insertElement("input");
		input.setAttribute("name", "fields[select_options]");

		var many = this.selectFragment.appendChild(Element.create("label")),

		checkbox = many.insertElement("input");
		checkbox.setAttribute("name", "fields[select_multiple]");
		checkbox.setAttribute("type", "checkbox");

		many.insertTextNode(" Allow selection of multiple items");
	},
	foreignBuild: function() {
		this.foreignFragment = document.createDocumentFragment();

		var section = this.foreignFragment.appendChild(Element.create("label"));
		section.insertTextNode("Section ");

		var selectBox = section.insertElement(document.getElementsByName("fields[parent_section]")[0].cloneNode(true));
		selectBox.setAttribute("name", "fields[foreign_section]");

		var many = this.foreignFragment.appendChild(Element.create("label")),

		checkbox = many.insertElement("input");
		checkbox.setAttribute("name", "fields[foreign_select_multiple]");
		checkbox.setAttribute("type", "checkbox");

		many.insertTextNode(" Allow selection of multiple items");
	},
	checkboxBuild: function() {
		this.checkboxFragment = document.createDocumentFragment();

		var label = this.checkboxFragment.appendChild(Element.create("label")),

		input = label.insertElement("input");
		input.setAttribute("name", "fields[default_state]");
		input.setAttribute("type", "checkbox");

		label.insertTextNode(" Checked by default");
	},
	uploadBuild: function() {
		this.uploadFragment = document.createDocumentFragment();

		var destination = this.uploadFragment.appendChild(Element.create("label"));
		destination.insertTextNode("Destination Folder");

		this.uploadSelectBox = destination.insertElement("select");
		this.uploadSelectBox.setAttribute("name", "fields[destination_folder]");

		this.request = Request.create("ajax/?action=workspace-folders", this.showFolders);
		this.request.post("");
	},
	showFolders: function(xml) {
		for (var path, i = 0; path = xml.getElementsByTagName("path")[i]; i++) {
			this.uploadSelectBox.options[i] =
				new Option(path.firstChild.data, path.firstChild.data);
		}
	},
	warnNameChange: function() {
		if (this.title.value == this.originalName) return true;

		if (!confirm("If you rename this custom field, you will also have to resave any data sources that use it. Are you sure you want to rename " + this.originalName + "?")) {
			this.title.value = this.originalName;
			return false;
		}

		return true;
	}
});

var Save = Task.extend({
	initialise: function() {
		var button = document.getElementsByName("action[save]")[0];

		if (button && button.hasAttribute("accesskey"))
			button.onmouseup = this.done;
	},
	done: function(event) {
		if (event.shiftKey) return;

		event.currentTarget.setAttribute("name", "action[done]");
	}
});

var ResizeTextarea = Task.extend({
	initialise: function() {
		var textareas = Elements.find("textarea"), resize = this.resize;

		textareas.each(function() {
			this.addEventListener("keypress", resize, false);
		});
	},
	resize: function(event) {
		var key = event.which || event.keyCode || event.charCode;

		if (key != 27) return;

		event.preventDefault();

		var textarea = event.currentTarget, size = textarea.getAttribute("rows") - 1;

		if (event.shiftKey && size > 5)
			textarea.setAttribute("rows", size);

		if (!event.shiftKey && size + 2 < 120)
			textarea.setAttribute("rows", size + 2);
	}
});

var EntryDate = Task.extend({
	initialise: function() {
		var input = document.getElementsByName("fields[publish_date]")[0];

		if (!input || input.getAttribute("type") == "hidden") return;

		input.setAttribute("type", "hidden");

		var label = input.parentNode;
		label.parentNode.insertBefore(input, label);
		label.parentNode.removeChild(label);

		this.calendar = Calendar.create(input.value);
		this.calendar.onchange = this.select;

		input.parentNode.insertBefore(this.calendar, input);
	},
	select: function() {
		var data = this.calendar.selectedDate + " " + this.calendar.selectedMonth + " " +
			this.calendar.selectedYear;

		document.getElementsByName("fields[publish_date]")[0].value = data;
	}
});

var Delete = Task.extend({
	initialise: function() {
		var deleteButton = document.getElementsByName("action[delete]")[0];

		if (deleteButton)
			deleteButton.onclick = this.doubleCheck;
	},
	doubleCheck: function() {
		var pageName = document.getElementsByTagName("h2")[0].firstChild.data;

		if (document.getElementsByName("fields[textformat]")[0])
			return confirm("Deleting this author will also delete their entries. Are you sure you want to delete " + pageName + "?");

		if (document.getElementsByName("fields[calendar_show]")[0])
			return confirm("Deleting this section will also delete any entries, comments, categories and custom fields that are associated with it. Are you sure you want to delete " + pageName + "?");

		if (!confirm("Are you sure you want to delete " + pageName + "?"))
			return false;
	}
});

var Configure = Task.extend({
	initialise: function() {
		this.config = document.getElementById("config");
		if (!this.config) return;

		this.wrapper = document.createElement("div");
		this.wrapper.appendChild(this.config);

		// Browser sniffing is wrong, kids.
		this.hideMe = (window.navigator.vendor != "Apple Computer, Inc.") ? null : "block";

		this.wrapper.style.display = "none";
		this.wrapper.style.overflow = "hidden";
		this.wrapper.style.height = 0;

		var h2 = document.getElementsByTagName("h2")[0];
		h2.parentNode.insertBefore(this.wrapper, h2.nextSibling);

		this.link = h2.getElementsByTagName("a")[0];
		this.link.addEventListener("click", this.toggle, false);

		this.link.addEventListener("click", Event.silence, false);
		this.link.addEventListener("mousedown", Event.silence, false);
	},
	toggle: function() {
		this.link.removeEventListener("click", this.toggle, false);

		if (this.wrapper.style.height != "auto") {
			this.wrapper.style.display = "block";
			Animation.run(this.update, 0, this.config.offsetHeight);

		} else Animation.run(this.update, this.config.offsetHeight, 0);
	},
	update: function(position, destination) {
		this.wrapper.style.height = Math.round(position) + "px";

		if (this.wrapper.offsetHeight == destination) {
			if (this.wrapper.offsetHeight > 0) {
				this.wrapper.style.height = "auto";

			} else {
				this.wrapper.style.display = this.hideMe || "none";
				fixRedrawBug();
			}

			this.link.addEventListener("click", this.toggle, false);

			return false;
		}

		return true;
	}
});

var Utilities = Task.extend({
	initialise: function() {
		var container = document.getElementById("utilities");
		this.dataSources = document.getElementsByName("fields[data_sources][]")[0];

		if (!container || !this.dataSources) return;

		this.events = document.getElementsByName("fields[events][]")[0];
		this.master = document.getElementsByName("fields[master]")[0];

		this.container = Element.create(container.getElementsByTagName("ul")[0]);
		this.request = Request.create("ajax/?action=utilities", this.populate);

		this.dataSources.onchange = this.events.onchange = this.fetch;
	},
	fetch: function() {
		this.container.parentNode.setAttribute("class", "loading");

		var option, data = "type=" + (this.master ? "page" : "master"),
			attachedDataSources = [], attachedEvents = [];

		for (var i = 0; option = this.dataSources.options[i]; i++) {
			if (!option.selected) continue;

			attachedDataSources.push(option.getAttribute("value"));
		}

		for (i = 0; option = this.events.options[i]; i++) {
			if (!option.selected) continue;

			attachedEvents.push(option.getAttribute("value"));
		}

		if (attachedDataSources.length > 0)
			data += "&datasources=" + attachedDataSources.join(",");

		if (attachedEvents.length > 0)
			data += "&events=" + attachedEvents.join(",");

		if (this.master && (this.master.value != "" || this.master.value != "None"))
			data += "&master=" + this.master.value;

		this.request.post(data);
	},
	populate: function(xml) {
		var fragment = Element.createFragment();

		for (var utility, link, i = 0; utility = xml.getElementsByTagName("utility")[i]; i++) {
			link = fragment.insertElement("li").insertElement("a");
			link.setAttribute("href", utility.getElementsByTagName("link")[0].firstChild.data);
			link.insertTextNode(utility.getElementsByTagName("name")[0].firstChild.data);
		}

		this.container.parentNode.removeAttribute("class");
		this.container.replaceContents(fragment);
	}
});

var Password = Task.extend({
	initialise: function() {
		if (!document.getElementById("login-details") || document.getElementsByName("fields[password]")[0]) return;

		var username = document.getElementsByName("fields[username]")[0];

		this.changePassword = Button.create(this.change, "Change password");
		this.changePassword.setAttribute("title", "Change password");
		this.changePassword.setAttribute("class", "change-password button");

		username.parentNode.parentNode.appendChild(this.changePassword);
	},
	change: function(event) {
		var fragment = Element.createFragment();

		var oldPassword = fragment.insertElement("label");
		oldPassword.insertTextNode("Current Password");
		oldPassword.insertElement("small");
		oldPassword.lastChild.insertTextNode("Leave blank to keep current password.");

		var oldInput = oldPassword.insertElement("input");
		oldInput.setAttribute("name", "fields[password]");
		oldInput.setAttribute("type", "password");

		var group = fragment.insertElement("div");
		group.setAttribute("class", "group");

		var newPassword = group.insertElement("label");
		newPassword.insertTextNode("New Password");

		var newInput = newPassword.insertElement("input");
		newInput.setAttribute("name", "fields[new_password]");
		newInput.setAttribute("type", "password");

		var confirmPassword = group.insertElement("label");
		confirmPassword.insertTextNode("Confirm New Password");

		var confirmInput = confirmPassword.insertElement("input");
		confirmInput.setAttribute("name", "fields[confirm_password]");
		confirmInput.setAttribute("type", "password");

		this.changePassword.parentNode.replaceChild(fragment, this.changePassword);

		oldInput.focus();
		oldInput.select();
	}
});

var AuthorAccess = Task.extend({
	initialise: function() {
		this.access = document.getElementsByName("fields[superuser]")[0];

		if (!this.access) return;

		this.admin = this.access.parentNode.parentNode.getElementsByTagName("p")[0];
		this.author = this.access.parentNode.parentNode.getElementsByTagName("label")[1];

		this.fragment = document.createDocumentFragment();

		if (this.access.value != 1)
			this.fragment.appendChild(this.admin);

		else this.fragment.appendChild(this.author);

		this.access.onchange = this.changeAccess;
	},
	changeAccess: function() {
		var old = (this.admin.parentNode != this.fragment) ? this.admin : this.author;

		this.fragment.appendChild(old.parentNode.replaceChild(this.fragment, old));
	}
});


var Login = Task.extend({
	initialise: function() {
		var username = document.getElementsByName("username")[0];

		if (!username || username.value.length > 0 || !document.getElementById("login")) return;

		username.focus();
	}
});

var Timezone = Task.extend({
	initialise: function() {
		this.selectBox = document.getElementsByName("settings[region][time_zone]")[0];
		this.checkBox = document.getElementsByName("settings[region][dst]")[0];

		if (this.selectBox && this.checkBox)
			this.selectBox.onchange = this.checkBox.onchange = this.update;
	},
	update: function() {
		var time = "Current time";

		for (var i = 0; i < this.selectBox.options.length; i++) {
			if (this.selectBox.options[i].selected) {
				time = this.selectBox.options[i].getAttribute("title");
				break;
			}
		}

		if (this.checkBox.checked) {
			var day = time.split(" ")[0];
			var hour = time.split(" ")[1];
			var meridian = time.split(" ")[2];

			hour = (hour.split(":")[0] % 12 + 1) + ":" + hour.split(":")[1];

			if (hour.split(":")[0] > 11) {
				meridian = (meridian == "am") ? "pm" : "am";
				var days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday",
					"Saturday"];

				for (var i = 0; meridian == "am" && i < days.length; i++) {
					if (day == days[i]) {
						day = (days[i + 1]) ? days[i + 1] : days[0];

						break;
					}
				}
			}

			time = day + " " + hour + " " + meridian;
		}

		this.selectBox.parentNode.getElementsByTagName("small")[0].firstChild.data = time;
	}
});

var CustomPageType = Task.extend({
	initialise: function() {
		this.select = document.getElementsByName("fields[type]")[0];

		if (!this.select || !document.getElementById("config")) return;

		this.label = this.select.parentNode;
		this.fragment = document.createDocumentFragment();

		this.help = this.fragment.appendChild(document.createElement("small"));
		this.help.setAttribute("class", "tip");
		this.help.appendChild(document.createTextNode("Press Esc to return"));

		this.custom = this.fragment.appendChild(document.createElement("input"));
		this.custom.setAttribute("name", "fields[type]");

		this.custom.onkeypress = this.escape;
		this.select.onchange = this.change;

		this.select.options[this.select.options.length] = new Option("Custom...", "custom");
	},
	change: function() {
		if (this.select.value != "custom") return;

		this.fragment.appendChild(this.label.replaceChild(this.fragment, this.select));

		this.custom.focus();
		this.custom.select();
	},
	escape: function(event) {
		if (event.keyCode != 27) return true;

		this.fragment.appendChild(this.help);
		this.fragment.appendChild(this.custom);

		this.label.appendChild(this.fragment.firstChild);
		this.select.options[0].selected = true;

		return false;
	}
});

var Attachment = Class.extend({
	create: function(container) {
		this.container = Element.create(container);

		var currentFiles = container.getElementsByTagName("ul")[0];

		this.uploaded = {};
		this.deletable = {};

		if (currentFiles) {
			for (var item, i = 0; item = currentFiles.getElementsByTagName("li")[i]; i++) {
				item.appendChild(Button.create(this.removeFile, "Delete")).setAttribute("class",
					"delete button");

				this.uploaded[item.getElementsByTagName("a")[0].firstChild.data] = item;
				this.deletable[item.getElementsByTagName("a")[0].firstChild.data] = true;
			}
		}

		this.directory = container.getElementsByTagName("input")[1].value;
		this.deleted = container.getElementsByTagName("input")[2];

		this.fragment = document.createDocumentFragment();

		var confirmButton = this.fragment.appendChild(Button.create(this.prepare, "Confirm"));
		confirmButton.setAttribute("class", "confirm button");

		this.file = container.getElementsByTagName("input")[0];
		this.file.onchange = this.includeConfirmation;

		this.prototype = this.file.cloneNode(true);
	},
	includeConfirmation: function() {
		if (this.fragment.childNodes.length > 0 && this.file.value)
			this.file.parentNode.appendChild(this.fragment);

		if (this.fragment.childNodes.length < 1 && !this.file.value)
			this.fragment.appendChild(this.file.parentNode.lastChild);
	},
	prepare: function() {
		var fileName = this.file.value.replace(/\\+/g, "/").split("/").pop();

		if (this.file.value)
			this.setFile("/" + this.directory + fileName);

		else alert("No file selected");
	},
	setFile: function(path) {
		if (this.uploaded[path]) {
			if (!confirm("This file already exists. Overwrite?")) return;

			this.removeFile(this.uploaded[path]);
		}

		if (!this.pending) {
			this.pending = this.container.insertAfter(Element.create("ul"), Element.create(this.file.parentNode));
		}

		this.uploaded[path] = this.pending.insertElement("li", this.pending.firstChild);
		this.uploaded[path].insertTextNode(path);
		this.uploaded[path].input = this.file;

		this.file.setAttribute("class", "ready");
		this.file = this.file.parentNode.insertBefore(this.prototype.cloneNode(true), this.file);
		this.file.onchange = this.includeConfirmation;
		this.includeConfirmation();

		var deleteButton = this.uploaded[path].appendChild(Button.create(this.removeFile, "Delete"));
		deleteButton.setAttribute("class", "delete button");
	},
	removeFile: function(event) {
		var file = event.currentTarget ? event.currentTarget.parentNode : event;

		var links = file.getElementsByTagName("a"),
			path = links.length > 1 ? links[0].firstChild.data : file.firstChild.data;

		this.uploaded[path] = null;

		if (this.deletable[path] && this.deleted.value.search(path) < 0)
			this.deleted.value += this.deleted.value ? "," + path : path;

		if (file.parentNode.getElementsByTagName("li").length < 2) {
			if (file.parentNode == this.pending)
				delete this.pending;

			file.parentNode.parentNode.removeChild(file.parentNode);

		} else file.parentNode.removeChild(file);

		if (file.input)
			file.input.parentNode.removeChild(file.input);
	}

});

var UploadFields = Task.extend({
	initialise: function() {
		if (!document.getElementById("entry")) return;

		var fields = Element.find("div");

		fields.each(function() {
			if (this.getAttribute("class") != "attachment") return false;

			Attachment.create(this);
		});
	}
});

var DataSources = Task.extend({
	initialise: function() {
		this.select = document.getElementsByName("fields[source]")[0];
		if (!this.select) return;

		this.categories = { "sections": null, "comments": null, "options": null,
			"authors": null, "navigation": null, "static_xml": null };

		var fieldset = document.getElementsByTagName("fieldset")[0];
		this.elements = Element.List.create(null, fieldset.getElementsByTagName("*"));
		this.elements.filter(function() {
			return /div|label|fieldset/i.test(this.nodeName) &&
				this.className.match(/sections|comments|options|authors|navigation|static_xml/);
		});

		var customise = this.customise, cancel = this.uncustomise, toggle = this.toggle;
		this.customFilters = Element.List.create(null, document.getElementsByTagName("select"));
		this.customFilters.filter(function() {
			var label = this.parentNode;
			if (label.getAttribute("class") != "custom-filter") return false;

			label.alternate = Element.create("label");
			label.alternate.appendChild(label.firstChild.cloneNode(true));

			label.select = this;
			label.alternate.alternate = label;

			label.alternate.help = Element.create("small");
			label.alternate.help.setAttribute("class", "tip");
			label.alternate.help.insertTextNode("Press Esc to return");

			label.alternate.input = label.alternate.appendChild(Element.create("input"));
			label.alternate.input.setAttribute("name", this.getAttribute("name"));
			label.alternate.input.setAttribute("value", "$param");

			this.options[this.options.length] = new Option("Custom...", "custom");

			label.alternate.input.onfocus = label.alternate.input.onblur = toggle;
			label.alternate.input.onkeypress = cancel;
			this.onchange = customise;

			return true;
		});

		this.includedFields = {
			box: Element.create(document.getElementsByName("fields[xml-elements][]")[0]),
			div: Element.create("div").insertTextNode("N/A").parentNode
		}
		this.includedFields.div.setAttribute("class", "inactive");

		var groups = this.includedFields.box.getElementsByTagName("optgroup");
		for (var group, range, i = 0; group = groups[i]; i++) {
			range = document.createRange();
			range.selectNodeContents(group);
			this.includedFields[group.getAttribute("label")] = range.extractContents();
		}
		this.includedFields.box.replaceContents();

		this.formatType = document.getElementsByName("fields[format_type]")[0];
		this.htmlEncode = document.getElementsByName("fields[html_encode]")[0].parentNode;
		this.limitOptions = this.elements[this.elements.length - 1];
		this.showPage = this.limitOptions.getElementsByTagName("label")[1];

		this.limit = document.getElementsByName("fields[max_records]")[0] ||
			document.getElementsByName("fields[max_months]")[0];
		this.flag = this.limit.parentNode.lastChild;

		this.help = Element.create("small");
		this.help.setAttribute("class", "tip");

		this.help.insertTextNode("Press Esc to limit by " +
			((this.limit.getAttribute("name") != "fields[max_months]") ? "months."
			: "records."));

		this.limit.onfocus = this.limit.onblur = this.limitHelp;
		this.limit.onkeypress = this.limitChange;
		this.formatType.onchange = this.changeFormatType() || this.changeFormatType;

		this.select.addEventListener("change", this.filter, false);
		this.filter();
	},
	filter: function() {
		var category = (this.select.value in this.categories) ? this.select.value : "sections";

		this.htmlEncode.style.display = "block";

		this.elements.filter(function() {
			this.style.display = (this.hasClass(category)) ? "block" : "none";

			return true;
		});

		if (category == "comments")
			this.limitOptions.style.display = this.showPage.style.display = "block";

		if (this.includedFields.div.parentNode)
			this.includedFields.div.parentNode.replaceChild(this.includedFields.box,
				this.includedFields.div);

		if (this.limit.modified) {
			this.flag.data = " record(s).";
			this.limit.setAttribute("name", "fields[max_records]");
			this.help.firstChild.data = "Press Esc to limit by months.";
		}

		this.limit.onfocus = this.limit.onblur = this.limitHelp;
		this.limit.onkeypress = this.limitChange;

		if (category == "sections") {
			var current = this.select.value, shown = [];

			this.customFilters.filter(function() {
				var match = (this.getAttribute("name").indexOf("[" + current + "]") != -1);

				if (match)
					shown.push(this.parentNode);
				this.parentNode.style.display = match ? "block" : "none";

				return true;
			});

			if (this.customFilters.length > 0 && shown.length == 0) {
				var f = /fieldset/i.test(this.customFilters[0].parentNode.parentNode.nodeName) ?
					this.customFilters[0].parentNode.parentNode
					: this.customFilters[0].parentNode.parentNode.parentNode;

				f.style.display = "none";
			}

			this.changeFormatType();
		}

		if (this.includedFields[this.select.value]) {
			var clone = this.includedFields[this.select.value].cloneNode(true);
			this.includedFields.box.replaceContents(clone);
		}

		fixRedrawBug();
	},
	customise: function(event) {
		var selectBox = event.currentTarget, current = selectBox.parentNode;

		if (selectBox.value != "custom") return;

		current.parentNode.replaceChild(current.alternate, current);
		current.alternate.input.focus();
		current.alternate.input.select();
	},
	uncustomise: function(event) {
		if (event.keyCode != 27) return true;

		var input = event.currentTarget, current = input.parentNode;

		current.alternate.select.options[0].selected = true;
		current.parentNode.replaceChild(current.alternate, current);
		current.help = current.removeChild(current.help);

		return false;
	},
	toggle: function(event) {
		var input = event.currentTarget, label = input.parentNode;

		if (label.help.parentNode)
			label.help = label.removeChild(label.help);

		else label.insertBefore(label.help, input);
	},
	changeFormatType: function(event) {
		var format = this.formatType.value,
			div = this.includedFields.div,
			box = this.includedFields.box;

		if (format != "archive-overview") {
			if (div.parentNode)
				div.parentNode.replaceChild(box, div);

		} else box.parentNode.replaceChild(div, box);

		if (format == "archive") {
			if (this.limit.getAttribute("name") != "fields[max_months]") {
				this.flag.data = " month(s).";
				this.limit.setAttribute("name", "fields[max_months]");
				this.help.firstChild.data = "Press Esc to limit by records.";
				this.limit.modified = true;
			}
		} else if (this.limit.modified) {
			this.flag.data = " record(s).";
			this.limit.setAttribute("name", "fields[max_records]");
			this.help.firstChild.data = "Press Esc to limit by months.";
		}

		this.htmlEncode.style.display = this.limitOptions.style.display =
			(format != "archive-overview") ? "block" : "none";

		this.limit.onfocus = this.limit.onblur = (format != "list") ? null : this.limitHelp;
		this.limit.onkeypress = (format != "list") ? null : this.limitChange;
		this.showPage.style.display = (format != "list") ? "none" : "block";
	},
	limitHelp: function() {
		if (!this.help.parentNode)
			this.limit.parentNode.appendChild(this.help);

		else this.help = this.limit.parentNode.removeChild(this.help);
	},
	limitChange: function(event) {
		if (event.keyCode != 27) return true;

		var toMonth = (this.limit.getAttribute("name") != "fields[max_months]");

		this.limit.setAttribute("name", toMonth ? "fields[max_months]" :
			"fields[max_records]");
		this.flag.data = toMonth ? " month(s)." : " record(s).";
		this.help.firstChild.data = toMonth ? "Press Esc to limit by records."
			: "Press Esc to limit by months.";

		return false;
	}
});


// Widget Toolbar

var Widget = Class.extend({
	identify: function() {
		return Element.createFragment();
	},
	display: function() {
		Toolbar.display(this);
	}
});

var SearchWidget = Widget.extend({
	create: function() {
		this.trigger = Button.create(this.display, "Search");
		this.trigger.setAttribute("title", "Search");

		this.request = Request.create("ajax/?action=search", this.populate);

		var container = this.insertElement("div").insertElement("div");

		var label = container.parentNode.insertElement("label", container);
		label.insertTextNode("Search Entries ");

		this.heading = container.insertElement("h3");
		this.heading.insertTextNode("Search Instructions");

		this.result = container.insertElement("p");
		this.result.insertTextNode("Enter search terms and press return.");

		this.input = label.insertElement("input");
		this.input.onkeypress = this.submit;
	},
	submit: function(event) {
		if (event.keyCode != 3 && event.keyCode != 13) return;

		if (this.input.value.length > 0) {
			this.heading.firstChild.data = "Results for \"" + this.input.value + "\"";

			var oldResult = this.result;

			this.result = Element.create("p").insertTextNode("Searching...").parentNode;
			this.result.setAttribute("class", "loading");

			oldResult.parentNode.replaceChild(this.result, oldResult);

			this.request.post("query=" + encodeURIComponent(this.input.value));
		}

		return false;
	},
	populate: function(xml) {
		var oldResult = this.result;

		var items = xml.getElementsByTagName("item");

		if (items.length > 0)
			this.result = Element.create("ul");

		else this.result = Element.create("p").insertTextNode("None found.").parentNode;

		for (var item, title, link, date, dd, i = 0; item = items[i]; i++) {
			title = item.getElementsByTagName("title")[0].firstChild.data;

			link = this.result.insertElement("li").insertElement("a");
			link.insertTextNode(title);
			link.setAttribute("href",
				item.getElementsByTagName("link")[0].firstChild.data.replace(/#38;/g, ""));
			link.setAttribute("class", item.getAttribute("class"));

			date = this.result.lastChild.insertElement("p");
			date.insertTextNode(item.getElementsByTagName("date")[0].firstChild.data);
			date.setAttribute("class", "date");

			if (item.getElementsByTagName("description")[0]) {
				dd = this.result.lastChild.insertElement("p");
				dd.insertElement("p").insertTextNode(item.getElementsByTagName("description")[0].firstChild.data);
			}
		}

		oldResult.parentNode.replaceChild(this.result, oldResult);
	}
});

var CalendarWidget = Widget.extend({
	create: function() {
		this.trigger = Button.create(this.display, "Calendar");
		this.trigger.setAttribute("title", "Calendar");

		this.request = Request.create("ajax/?action=calendar", this.populate);

		this.calendar = this.insertElement("div").appendChild(Calendar.create());
		this.calendar.onchange = this.select;

		this.result = this.firstChild.insertElement("div");

		var fullDate = this.calendar.selectedDate + " " + this.calendar.selectedMonth + " " +
			this.calendar.selectedYear;

		this.heading = this.result.insertElement("h3");
		this.heading.insertTextNode("Entries on " + fullDate + " ");

		this.createNew = this.heading.insertElement("a");
		this.createNew.setAttribute("class", "create button");
		this.createNew.insertTextNode("Create New");

		var prev = this.calendar.getElementsByTagName("a")[0],
			next = this.calendar.getElementsByTagName("a")[1];

		prev.addEventListener("click", this.change, false);
		next.addEventListener("click", this.change, false);

		this.months = {};
	},
	display: function() {
		Toolbar.display(this);

		this.change();
	},
	change: function() {
		var month = this.calendar.heading.data.split(" ")[0],
			year = this.calendar.heading.data.split(" ")[1];

		if (!this.months[this.calendar.heading.data])
			this.request.post("month=" + month + "&year=" + year);

		else this.populate(this.months[this.calendar.heading.data]);

		// Acquire Sections

		if (!this.sections) {
			var sections = [];
		
			Element.create(document.getElementById("navigation")).find("a").each(function() {
				if (!/&_sid=/.test(this.href)) return;

				sections.unshift("?page=/publish/section/new/&_sid=" + this.href.split("&_sid=").pop());
				sections.unshift(this.firstChild.data);
			});

			this.sections = this.heading.insertElement("select", this.createNew);

			for (var i = 0; i < sections.length / 2; i++)
				this.sections.options[i] = new Option(sections[2 * i], sections[2 * i + 1]);

			this.setSection();

			this.sections.addEventListener("change", this.setSection, false);
		}

		var caption = this.calendar.selectedMonth + " " + this.calendar.selectedYear;
	},
	setSection: function() {
		var href = this.sections.value + "&date=" + this.calendar.selectedDate + "&month=" + this.calendar.selectedMonth + "&year=" + this.calendar.selectedYear;

		this.createNew.setAttribute("href", href);
	},
	select: function() {
		var xml = this.months[this.calendar.heading.data];

		var fullDate = this.calendar.selectedDate + " " + this.calendar.selectedMonth + " " + this.calendar.selectedYear;

		this.heading.firstChild.data = "Entries on " + fullDate;
		this.result.replaceContents(this.heading);

		if (!xml)
			return this.result.insertElement("p").insertTextNode("Loading...");

		var items = xml.getElementsByTagName("item");

		for (var list, item, i = 0; item = items[i]; i++) {

			if (item.getElementsByTagName("date")[0].firstChild.data - 0 !=
					this.calendar.selectedDate)
				continue;

			if (!list) list = this.result.insertElement("ul");

			for (var link, itemLink, title, j = 0;
					title = item.getElementsByTagName("title")[j]; j++) {

				link = list.insertElement("li").insertElement("a");
				link.insertTextNode(title.firstChild.data);

				itemLink = item.getElementsByTagName("link")[j];

				link.setAttribute("href", itemLink.firstChild.data);
				link.setAttribute("class", itemLink.parentNode.getAttribute("class"));
			}
		}

		if (!list) this.result.insertElement("p").insertTextNode("None found.");

		this.setSection();
	},
	populate: function(xml) {
		var month = xml.getElementsByTagName("month")[0].firstChild.data;
		this.months[month] = xml;

		if (this.calendar.heading.data != month) return;

		var links = this.calendar.getElementsByTagName("tbody")[0].getElementsByTagName("a"),
			items = xml.getElementsByTagName("item");

		for (var link, i = 0; link = links[i]; i++) {
			if (link.firstChild.data == this.calendar.selectedDate && this.calendar.heading.data
					== this.calendar.selectedMonth + " " + this.calendar.selectedYear)
				this.select();

			for (var item, j = 0; item = items[j]; j++) {
				if (link.firstChild.data == item.getElementsByTagName("date")[0].firstChild.data
						- 0)
					link.addClass("active");
			}
		}
	}
});

var StatusWidget = Widget.extend({
	create: function() {
		this.trigger = Button.create(this.display, "Status");
		this.trigger.setAttribute("title", "Status");

		this.request = Request.create("ajax/?action=status", this.populate);

		var container = this.insertElement("div");

		var list = container.insertElement("ul");
		list.setAttribute("id", "status");

		this.entries = list.insertElement("li");
		this.comments = list.insertElement("li");
		this.version = list.insertElement("li");

		this.entries.insertElement("h3").insertTextNode("Recent Entries");
		this.comments.insertElement("h3").insertTextNode("Recent Comments");
		this.version.insertElement("h3").insertTextNode("System Updates");

		this.request.post("done=false");
	},
	display: function() {
		Toolbar.display(this);

		if (this.populated && this.trigger.hasAttribute("class"))
			this.request.post("done=true");

		this.viewed = true;
	},
	populate: function(xml) {
		if (xml.getElementsByTagName("*").length < 2) return this.done();

		var entries = xml.getElementsByTagName("entry"),
			comments = xml.getElementsByTagName("comment"),
			campfire = xml.getElementsByTagName("campfire"),
			version = xml.getElementsByTagName("version")[0],
			result, link;

		if (entries.length > 0)
			result = this.entries.insertElement("ul");
		else this.entries.insertElement("p").insertTextNode("None found.");

		for (var entry, i = 0; entry = entries[i]; i++) {
			link = result.insertElement("li").insertElement("a");
			link.setAttribute("href", entry.getElementsByTagName("link")[0].firstChild.data);
			link.setAttribute("class", entry.getAttribute("class"));
			link.insertTextNode(entry.getElementsByTagName("title")[0].firstChild.data);

			if (entry.hasAttribute("new")) {
				this.trigger.setAttribute("class", "new");
				this.trigger.setAttribute("title", "A new entry has appeared.");

				link.addClass("new");
			}
		}

		if (comments.length > 0)
			result = this.comments.insertElement("ul");
		else this.comments.insertElement("p").insertTextNode("None found.");

		for (var comment, j = 0; comment = comments[j]; j++) {
			link = result.insertElement("li").insertElement("a");
			link.setAttribute("href", comment.getElementsByTagName("link")[0].firstChild.data);
			link.setAttribute("class", comment.getAttribute("class"));
			link.insertTextNode(comment.getElementsByTagName("title")[0].firstChild.data);

			if (comment.hasAttribute("new")) {
				this.trigger.setAttribute("class", "new");
				this.trigger.setAttribute("title", "A new comment has been posted.");

				link.addClass("new");
			}
		}

		var info = "You are using " +
			version.getElementsByTagName("current")[0].firstChild.data;

		var update = version.getElementsByTagName("update")[0];

		if (update) {
			this.trigger.setAttribute("class", "new");
			this.trigger.setAttribute("title", "A Symphony update is available!");

			info += ".";

			var p = this.version.insertElement("p")
			p.insertTextNode("A new update is available. ");
			p.setAttribute("class", "update");

			link = p.insertElement("a").insertTextNode("Get " +
				update.firstChild.data + "!", p).parentNode;
			link.setAttribute("href", "http://accounts.symphony21.com/");

		} else {
			info += " which is the latest version available."
		}

		if (campfire.length > 0)
			result = this.version.insertElement("ul");

		for (var service, k = 0; service = campfire[k]; k++) {
			link = result.insertElement("li").insertElement("a");
			link.setAttribute("href", service.getElementsByTagName("link")[0].firstChild.data);
			link.setAttribute("class", "new campfire");
			link.insertTextNode(service.getElementsByTagName("service")[0].firstChild.data);

			this.trigger.setAttribute("class", "new");
			this.trigger.setAttribute("title", "New Campfire services updates are available.");
		}

		this.version.insertElement("p").insertTextNode(info);

		if (this.viewed) {
			this.request.post("done=true");
			this.done();
		}

		this.populated = true;
	},
	done: function() {
		this.trigger.removeAttribute("class");
		this.trigger.removeAttribute("title");
	}
});

var Toolbar = Task.extend({
	initialise: function() {
		var navigation = document.getElementById("navigation");

		if (!navigation) return;

		this.search = SearchWidget.create();
		this.calendar = CalendarWidget.create();
		this.status = StatusWidget.create();

		var form = document.forms[0];

		this.container = Element.create("div");
		this.container.setAttribute("id", "widget");
		form.insertBefore(this.container, form.firstChild);

		if (document.getElementById("detail")) {
			this.fieldset = Element.create(document.getElementsByTagName("fieldset")[0]);
			this.fieldset.initY = this.fieldset.getPosition().y;
		}

		if (document.getElementById("notice"))
			this.container.setAttribute("class", "sub");

		var list = Element.create("ul");
		list.setAttribute("id", "toolbar");

		list.insertElement("li").appendChild(this.search.trigger);
		list.insertElement("li").appendChild(this.calendar.trigger);
		list.insertElement("li").appendChild(this.status.trigger);

		document.body.insertBefore(list, navigation);
	},
	display: function(widget) {
		this.search.trigger.removeEventListener("click", this.search.display, false);
		this.calendar.trigger.removeEventListener("click", this.calendar.display, false);
		this.status.trigger.removeEventListener("click", this.status.display, false);

		if ((this.previous = this.active) != (this.active = widget)) {

			if (!this.previous) {
				this.active.trigger.parentNode.setAttribute("class", "active");
				this.container.appendChild(this.active);
			}

			Animation.run(this.roll, this.container.offsetHeight,
				275 - this.container.offsetHeight);

		} else {
			this.active = null;

			Animation.run(this.roll, 275, 0);
		}
	},
	roll: function(position, destination) {
		position = Math.round(position);

		this.container.style.height = position + "px";

		// Browser sniffing is wrong, kids.
		if (window.navigator.userAgent.match("Gecko") &&
				!window.navigator.userAgent.match("KHTML") &&
				!window.navigator.userAgent.match("Opera"))
			document.getElementById("user").style.top = position + "px";

		if (this.fieldset)
			this.fieldset.style.top = this.fieldset.initY + position + "px";

		if (position != destination) return true;

		if (this.active) {
			if (this.previous && this.previous.trigger.parentNode.hasAttribute("class")) {
				this.change(this.previous, this.active);

				window.setTimeout(Animation.run, 32, this.roll, 0, 275);

				return false;
			}

		} else {
			this.change(this.previous);

			fixRedrawBug();
		}

		this.search.trigger.addEventListener("click", this.search.display, false);
		this.calendar.trigger.addEventListener("click", this.calendar.display, false);
		this.status.trigger.addEventListener("click", this.status.display, false);

		var input = this.container.getElementsByTagName("input")[0];
		if (input) {
			input.focus();
			input.select();
		}

		return false;
	},
	change: function(previous, widget) {
		var range = document.createRange();
		range.selectNodeContents(this.container);

		previous.appendChild(range.extractContents());
		previous.trigger.parentNode.removeAttribute("class");

		if (!widget) return;

		widget.trigger.parentNode.setAttribute("class", "active");
		this.container.appendChild(widget);
	}
});


// Debug Page

window.addEventListener("load", function() {
	if (!document.getElementById("view")) return;

	var navigation = document.getElementsByTagName("ul")[0],
		links = Element.List.create(null, navigation.getElementsByTagName("a")),
		lineNumbers = document.getElementById("toggle-line-numbers"),
		target;

	function navigate() {
		document.getElementById("active").removeAttribute("id");
		this.parentNode.setAttribute("id", "active");

		target = document.getElementById(this.href.split("#").pop());

		Elements.find(target.nodeName).each(function() {
			if (this == navigation) return;

			this.style.display = (this != target) ? "none" : "block";
		});
	};

	navigate.call(links[0]);

	links.addEventListener("click", navigate, false);
	links.addEventListener("click", Event.silence, false);

	lineNumbers.addEventListener("click", function(event) {
		var nodeName = (target.nodeName.toLowerCase() != "ol") ? "ol" : "ul";

		Elements.find(target.nodeName).each(function() {
			if (this == navigation) return;

			var range = document.createRange();
			range.selectNodeContents(this);

			var node = document.createElement(nodeName);
			node.setAttribute("id", this.id);
			node.style.display = this.style.display;

			if (this == target) target = node;

			node.appendChild(range.extractContents());
			this.parentNode.replaceChild(node, this);
		});

		event.preventDefault();
	}, false);

}, false);


// Redraw bug in Safari

function fixRedrawBug() {
	// Browser sniffing is wrong, kids.
	if (!/Apple/i.test(window.navigator.vendor)) return false;

	window.resizeBy(-1, 0);
	window.resizeBy(1, 0);

	return true;
};
