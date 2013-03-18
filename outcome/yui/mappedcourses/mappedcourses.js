YUI.add('moodle-core_outcome-mappedcourses', function(Y) {
    var NAME = 'core_outcome_mappedcourses',

    // Shortcuts, etc
        Lang = Y.Lang,
        NODE_CONTAINER,
        COURSE_LIST = new Y.ModelList(),
        URL_AJAX = M.cfg.wwwroot + '/outcome/ajax.php',
        PANEL = 'panel',
        LINK_ACTION = 'a[data-action=mappedcourses]',
        ACTION_CONTAINER = '#manage-outcome-sets',
        TEMPLATE_COMPILED,
        TEMPLATE = '<ul>' +
            '{{#each courses}}' +
            '<li><a href="{{url}}" title="{{title}}">({{shortname}}) {{fullname}}</a></li>' +
            '{{/each}}' +
            '</ul>';

    var MAPPEDCOURSES = function() {
        MAPPEDCOURSES.superclass.constructor.apply(this, arguments);
    };

    Y.extend(MAPPEDCOURSES, Y.Base,
        {
            /**
             * Do our template setup
             */
            initializer: function() {
                TEMPLATE_COMPILED = Y.Handlebars.compile(TEMPLATE);

                // The container will hold our rendered template
                NODE_CONTAINER = Y.Node.create('<div></div>');
                NODE_CONTAINER.addClass(Y.ClassNameManager.getClassName(NAME, 'course', 'list'));
                this.get(PANEL).set('bodyContent', NODE_CONTAINER);
            },

            /**
             * Show the panel
             */
            show_panel: function() {
                this._do_io({
                    contextid: this.get('contextId'),
                    outcomesetid: this.get('outcomeSetId'),
                    action: 'get_mapped_courses'
                }, function(data) {
                    COURSE_LIST.reset(data.courses);
                    this._render_panel_ui();
                    this.get(PANEL).set('headerContent', data.heading);
                    this.get(PANEL).show();
                });
            },

            /**
             * Renders the panel content
             * @private
             */
            _render_panel_ui: function() {
                NODE_CONTAINER.setHTML(TEMPLATE_COMPILED({
                    courses: COURSE_LIST.toJSON()
                }));
            },

            /**
             * Creates our panel and attaches listeners
             * @returns {Y.Panel}
             * @private
             */
            _create_panel: function() {
                // todo: what happens if panel gets too tall?
                var panel = new Y.Panel({
                    srcNode: Y.Node.create('<div></div>'),
                    headerContent: M.str.outcome.mappedcourses,
                    centered: true,
                    render: true,
                    visible: false,
                    modal: true,
                    zIndex: 1000
                });

                panel.get('srcNode').addClass(Y.ClassNameManager.getClassName(NAME, 'panel'));
                panel.plug(M.core_outcome.accessiblepanel);
                panel.addButton({
                    value: M.str.outcome.close,
                    action: function(e) {
                        e.preventDefault();
                        this.hide();
                    }
                });

                return panel;
            },

            /**
             * Helper method to do a AJAX request and to do error handling
             * @param data
             * @param callback
             * @private
             */
            _do_io: function(data, callback) {
                Y.io(URL_AJAX, {
                    context: this,
                    data: data,
                    on: {
                        complete: function(id, response) {
                            try {
                                var data = Y.JSON.parse(response.responseText);
                                if (Lang.isValue(data.error)) {
                                    new M.core.ajaxException(data);
                                    return;
                                }
                            } catch (e) {
                                new M.core.exception(e);
                                return;
                            }
                            callback.call(this, data);
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
                contextId: {},
                /**
                 * Show the mapped courses for this outcome set
                 */
                outcomeSetId: {},
                /**
                 * The actual panel
                 */
                panel: { readOnly: true, valueFn: '_create_panel' }
            }
        }
    );

    M.core_outcome = M.core_outcome || {};
    /**
     * This is the default implementation - setup a listener for a link
     * to be clicked.  On click, show the panel.
     * @param config
     */
    M.core_outcome.init_mappedcourses = function(config) {
        var panel = new MAPPEDCOURSES(config);
        Y.one(ACTION_CONTAINER).delegate('click', function(e) {
            e.preventDefault();
            panel.set('outcomeSetId', e.target.getData('outcomesetid'));
            panel.show_panel();
        }, LINK_ACTION);
    };
}, '@VERSION@', {
    requires: ['handlebars', 'json-parse', 'model-list', "classnamemanager", 'moodle-core_outcome-accessiblepanel', 'moodle-core-notification']
});
