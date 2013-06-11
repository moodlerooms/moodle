YUI.add('moodle-core_outcome-simpleio', function(Y) {
    var NAME = 'core_outcome_simpleio',

    // Shortcuts, etc
        Lang = Y.Lang,
        URL_AJAX = M.cfg.wwwroot + '/outcome/ajax.php';

    var SIMPLEIO = function() {
        SIMPLEIO.superclass.constructor.apply(this, arguments);
    };

    Y.extend(SIMPLEIO, Y.Base,
        {
            /**
             * Helper method to do a AJAX request and to do error handling
             * @param {Object} data
             * @param {Function} fn
             * @param {Object} context Optional argument that specifies what 'this' refers to.
             * @param {String} method POST, GET, etc
             */
            send: function(data, fn, context, method) {
                if (!Lang.isString(method)) {
                    method = 'GET';
                }
                if (Lang.isUndefined(data.contextid) && !Lang.isUndefined(this.get('contextId'))) {
                    data.contextid = this.get('contextId');
                }
                Y.io(this.get('url'), {
                    method: method,
                    data: data,
                    on: {
                        complete: function(id, response) {
                            var data = {};
                            try {
                                data = Y.JSON.parse(response.responseText);
                            } catch (e) {
                                e.zIndex = 10000;
                                new M.core.exception(e);
                                return;
                            }
                            if (Lang.isValue(data.error)) {
                                data.zIndex = 10000;
                                new M.core.ajaxException(data);
                            } else {
                                fn.call(context, data);
                            }
                        }
                    }
                });
            }
        },
        {
            NAME: NAME,
            ATTRS: {
                /**
                 * Current context ID, used for AJAX requests
                 */
                contextId: { value: undefined },
                /**
                 * Used for requests
                 */
                url: { value: URL_AJAX, validator: Lang.isString }
            }
        }
    );

    M.core_outcome = M.core_outcome || {};
    M.core_outcome.SimpleIO = SIMPLEIO;
}, '@VERSION@', {
    requires: ['base', 'io-base', 'json-parse', 'moodle-core-notification']
});
