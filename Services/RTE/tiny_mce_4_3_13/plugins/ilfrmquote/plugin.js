/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */

(function () {
	tinymce.PluginManager.requireLangPack('ilfrmquote');

	tinymce.PluginManager.add('ilfrmquote', function (editor, url) {
		var that = this;

		editor.addButton('ilFrmQuoteAjaxCall', {
			title: editor.editorManager.i18n.translate('quote'),
			cmd:   'ilFrmQuoteAjaxCall',
			image: url + '/images/quote.gif'
		});

		editor.addCommand('ilFrmQuoteAjaxCall', function () {
			if (ilFrmQuoteAjaxHandler) {
				ilFrmQuoteAjaxHandler(function(html) {
					var uid = 'frm_quote_' + new Date().getTime();

					html = that.ilfrmquote2html(html.toString()) + '<p id="' + uid + '">&nbsp;</p>';

					if (!self.isWindow && tinymce.isIE) {
						self.editor.selection.moveToBookmark(self.editor.windowManager.bookmark);
					}

					editor.execCommand('mceInsertRawHTML', false, html, {
						skip_focus: 1
					});
				});
			}
		});

		editor.on('GetContent', function(e) {
			e.content = that.html2ilfrmquote(e.content);
		});
		editor.on('SetContent', function(e) {
			e.content = that.ilfrmquote2html(e.content);
		});
		editor.on('BeforeSetContent', function(e) {
			e.content = that.ilfrmquote2html(e.content);
		});

		this.html2ilfrmquote = function (s) {
			s = tinymce.trim(s);

			function rep(re, str) {
				s = s.replace(re, str);
			};

			var startZ = that.substr_count(s, "<blockquote");
			var endZ   = that.substr_count(s, "</blockquote>");

			if (startZ > endZ) {
				var diff = startZ - endZ;
				for (var i = 0; i < diff; i++) {
					s += "</blockquote>";
				}
			}
			else if (startZ < endZ) {
				var diff = endZ - startZ;
				for (var i = 0; i < diff; i++) {
					s = "<blockquote class=\"ilForumQuote\">" + s;
				}
			}
			rep(/<blockquote[\s]*?class="ilForumQuote"[\s]*?>[\s]*?<div[\s]*?class="ilForumQuoteHead"[\s]*?>[\s\S]*?\(([\s\S]*?)\)<\/div>/gi, "[quote=\"$1\"]");
			rep(/<blockquote(.*?)class="ilForumQuote"(.*?)>/gi, "[quote]");
			rep(/<\/blockquote>/gi, "[/quote]");
			return s;
		};

		this.ilfrmquote2html = function (s) {
			s = tinymce.trim(s);

			function rep(re, str) {
				s = s.replace(re, str);
			};

			var startZ = that.substr_count(s, "[quote");
			var endZ   = that.substr_count(s, "[/quote]");

			if (startZ > endZ) {
				var diff = startZ - endZ;
				for (var i = 0; i < diff; i++) {
					s += "[/quote]";
				}
			}
			else if (startZ < endZ) {
				var diff = endZ - startZ;
				for (var i = 0; i < diff; i++) {
					s = "[quote]" + s;
				}
			}

			rep(/\[quote="(.*?)"\]/gi, "<blockquote class=\"ilForumQuote\"><div class=\"ilForumQuoteHead\">" + editor.editorManager.i18n.translate('quote') + " ($1)</div>");
			rep(/\[quote]/gi, "<blockquote class=\"ilForumQuote\">");
			rep(/\[\/quote\]/gi, "</blockquote>");

			return s;
		};

		this.substr_count = function (haystack, needle, offset, length) {
			var pos = 0, cnt = 0;

			haystack += '';
			needle += '';
			if (isNaN(offset)) {
				offset = 0;
			}
			if (isNaN(length)) {
				length = 0;
			}
			offset--;

			while ((offset = haystack.indexOf(needle, offset + 1)) != -1) {
				if (length > 0 && (offset + needle.length) > length) {
					return false;
				} else {
					cnt++;
				}
			}

			return cnt;
		};
	});

})();