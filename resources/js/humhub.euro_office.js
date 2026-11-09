humhub.module('euro_office', function (module, require, $) {
    var client = require('client');
    var modal = require('ui.modal');
    var status = require('ui.status');
    var object = require('util').object;
    var Widget = require('ui.widget').Widget;
    var event = require('event');

    /**
     * While a close request is pending, closing the modal again closes it immediately
     * (e.g. if the editor does not respond because of an error)
     */
    var CLOSE_REQUEST_DURATION = 10000;

    var apiPromises = {};

    /**
     * Prevents the automatic page reload of HumHub on CSP violations while the editor is opened
     *
     * Before HumHub 1.20, a violation of the "script-src" directive reloads the page (see humhub.client.js),
     * which closes the editor. The reload is skipped if the last one was done recently.
     * @todo Remove when HumHub 1.20 is the minimum version
     */
    var preventCspViolationReload = function () {
        try {
            localStorage.setItem('cspViolationReloadTime', Date.now().toString());
        } catch (e) {
            // localStorage is not available
        }
    };

    // As early as possible, the violation may occur while the editor modal is loaded
    preventCspViolationReload();

    /**
     * Returns the CSP nonce of the current page, if any
     */
    var getNonce = function () {
        var script = document.querySelector('script[nonce]');
        return script ? script.nonce || script.getAttribute('nonce') : null;
    };

    /**
     * Loads the Docs API script of the document server once
     */
    var loadApi = function (url) {
        if (window.DocsAPI) {
            return Promise.resolve(window.DocsAPI);
        }

        if (!apiPromises[url]) {
            apiPromises[url] = new Promise(function (resolve, reject) {
                var script = document.createElement('script');
                var nonce = getNonce();
                if (nonce) {
                    // Allows the script with a Content Security Policy that does not list the document server
                    script.nonce = nonce;
                }
                script.src = url;
                script.onload = function () {
                    window.DocsAPI ? resolve(window.DocsAPI) : reject(new Error('DocsAPI not available'));
                };
                script.onerror = function () {
                    delete apiPromises[url];
                    reject(new Error('Could not load ' + url));
                };
                document.head.appendChild(script);
            });
        }

        return apiPromises[url];
    };

    var Editor = function (node, options) {
        Widget.call(this, node, options);
    };

    object.inherits(Editor, Widget);

    Editor.prototype.init = function () {
        var that = this;
        preventCspViolationReload();
        this.modal = modal.get('#euro-office-modal');
        this.closing = false;
        this.closeRequestPending = false;

        loadApi(this.options.apiUrl).then(function (DocsAPI) {
            var config = $.extend({}, that.options.config, {
                width: '100%',
                height: '100%',
                events: {
                    // The editor has its own close button, the one of the modal is only needed until the editor is ready
                    onAppReady: function () {
                        that.modal.$.find('.modal-header .btn-close').hide();
                    },
                    onRequestClose: function () {
                        that.closeModal();
                    },
                    onError: function (evt) {
                        module.log.error(evt && evt.data ? evt.data.errorDescription : evt, true);
                    }
                }
            });
            that.docEditor = new DocsAPI.DocEditor(that.$.find('.euro-office-placeholder').attr('id'), config);
        }).catch(function (e) {
            module.log.error(module.text('error.loadApi'), true);
            module.log.error(e);
        });

        // Closing the modal (e.g. with "X" or ESC) first asks the editor to finish pending changes
        // If there are unsaved changes, the editor asks the user and calls onRequestClose on confirmation
        this.modal.$.on('hide.bs.modal.euro_office', function (evt) {
            if (that.closing || that.closeRequestPending
                || !that.docEditor || typeof that.docEditor.requestClose !== 'function') {
                return;
            }
            evt.preventDefault();
            that.closeRequestPending = true;
            setTimeout(function () {
                that.closeRequestPending = false;
            }, CLOSE_REQUEST_DURATION);
            that.docEditor.requestClose();
        });

        this.modal.$.one('hidden.bs.modal', function () {
            that.modal.$.off('hide.bs.modal.euro_office');
            that.destroy();
            that.modal.clear();
            if (that.options.edit) {
                status.success(module.text('info.saving'));
            }
        });
    };

    Editor.prototype.closeModal = function () {
        this.closing = true;
        this.modal.close();
    };

    Editor.prototype.destroy = function () {
        if (this.docEditor) {
            this.docEditor.destroyEditor();
            this.docEditor = null;
        }
    };

    var createSubmit = function (evt) {
        client.submit(evt).then(function (response) {
            var modalWindow = modal.get('#euro-office-modal');
            if (response.success) {
                event.trigger('humhub:file:created', [response.file]);
                modalWindow.load(response.editorUrl);
                modalWindow.show();
            } else {
                modalWindow.setDialog(response.output);
            }
        }).catch(function (e) {
            module.log.error(e, true);
        });
    };

    module.export({
        Editor: Editor,
        createSubmit: createSubmit
    });
});
