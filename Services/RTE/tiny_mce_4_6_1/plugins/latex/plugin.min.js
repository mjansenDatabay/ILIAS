/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */

(function() {

	tinymce.PluginManager.requireLangPack('latex');

	tinymce.PluginManager.add('latex', function(editor, url) {
		var baseurl = url;

		function insertLatex(content) {
			if (content && content.length > 0) {
				content = content.replace(new RegExp('\\$([^\\$]*)?\\$', 'g'), "<span class=\"latex\">$1</span>");
				content = content.replace(new RegExp('\\\\\\[', 'gi'), "<span class=\"latex\">");
				content = content.replace(new RegExp('\\\\\\]', 'gi'), "</span>");

				if (!self.isWindow && tinymce.isIE) {
					self.editor.selection.moveToBookmark(self.editor.windowManager.bookmark);
				}
				editor.execCommand('mceInsertRawHTML', false, content, {
					skip_focus: 1
				});
			}
		}

		editor.addButton('pastelatex', {
			tooltip: editor.editorManager.i18n.translate('paste_desc'),
			image:  baseurl + '/images/pastelatex.gif',
			onclick: function() {
				if ((editor.getParam('paste_use_dialog', true)) || (!tinymce.isIE)) {
					editor.windowManager.open({
						resizable: true,
						maximizable: true,
						title: editor.editorManager.i18n.translate('paste_desc'),
						body: [
							{
								type: 'label',
								text: editor.editorManager.i18n.translate('paste_title')
							},
							{
								type:      'textbox',
								multiline: true,
								minWidth:  500,
								minHeight: 300,
								name:      'htmlSource'
							}
						],
						onsubmit: function(e) {
							insertLatex(e.data.htmlSource);
						}
					});
				} else {
					insertLatex(clipboardData.getData('Text'));
				}
			}
		});

		editor.addButton('latex', {
			tooltip: editor.editorManager.i18n.translate('desc'),
			image:  baseurl + '/images/latex.gif',
			onclick: function() {

				var textbox = tinymce.ui.Factory.create({
					type:      'textbox',
					multiline: true,
					minWidth:  500,
					minHeight: 300,
					name:      'latex_code'
				});

				var preview = tinymce.ui.Factory.create({
					type:      'panel',
					id:        'preview',
					minHeight: 100
				});

				var typewatcher = function(){
					var handle = null;
					return function(callback, ms) {
						clearTimeout(handle);
						handle = setTimeout(callback, ms);
					}
				}();

				var onLatexCodeChanged = function() {
					//preview.innerHtml('x'); // This causes an error when called from onPostRender event
					if (this.getEl().value) {
						preview.innerHtml('\\(' + this.getEl().value + '\\)');
						typewatcher(function() {
							if (typeof MathJax != 'undefined') {
								MathJax.Hub.Queue(['Typeset', MathJax.Hub]);
							}
						}, 500);
					} else {
						preview.innerHtml('');
					}
				};

				textbox.on('keyup', function() {
					onLatexCodeChanged.call(this);
				});

				editor.windowManager.open({
					close_previous: true,
					resizable: true,
					maximizable: true,
					title: editor.editorManager.i18n.translate('latex_code'),
					body: [
						{
							type: 'label',
							text: editor.editorManager.i18n.translate('code_input')
						},
						textbox,
						{
							type: 'label',
							text: editor.editorManager.i18n.translate('preview')
						},
						preview
					],
					onPostRender: function(e) {
						var value = '', elm = editor.selection.getNode();

						if (elm != null) {
							var id = ("getAttribute" in elm) ? elm.getAttribute("class") : '';
							if (id == "latex") {
								var text = "";
								for (var i = 0; i < elm.childNodes.length; i++) {
									text = text + elm.childNodes[i].data;
								}
								if (text != 'undefined') {
									value = text;
								}
							} else {
								value = editor.selection.getContent({format : 'text'});
							}
						} else {
							value = editor.selection.getContent({format : 'text'});
						}
						textbox.value(value);
						onLatexCodeChanged.call(textbox);
					},
					onsubmit: function(e) {
						var elm = editor.selection.getNode(), latex_code = textbox.getEl().value;

						editor.execCommand('mceBeginUndoLevel');
						if (latex_code.length > 0) {
							if (elm == null)  {
								editor.execCommand('mceInsertContent', false, '<span class="latex">' + latex_code + '</span>');
							} else {
								var id = elm.getAttribute('class');
								if (id == 'latex') {
									elm.innerHTML = "";
									editor.execCommand('mceRemoveNode', false, elm);
									editor.execCommand('mceInsertContent', false, '<span class="latex">' + latex_code + '</span>');
								} else {
									editor.execCommand('mceInsertContent', false, '<span class="latex">' + latex_code + '</span>');
								}
							}
						}
						editor.execCommand('mceEndUndoLevel');
						editor.windowManager.close();
					}
				});
			}
		});
	});

})();