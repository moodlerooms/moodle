YUI.add('moodle-core_outcome-scrollpanel', function(Y) {
    var NAME = 'core_outcome_scrollpanel',

    // Shortcuts, etc
        HOST = "host",
        BODY_NODE = "bodyNode",
        WIN_SUBSCRIPTION = undefined,
        OLD_OVERFLOW = undefined,
        SCROLLTOP = undefined,
        Lang = Y.Lang;

    var SCROLLPANEL = function() {
        SCROLLPANEL.superclass.constructor.apply(this, arguments);
    };

    Y.extend(SCROLLPANEL, Y.Plugin.Base,
        {
            /**
             * Setup the event listener
             */
            initializer: function() {
                if (this.get(HOST) instanceof Y.Widget) {
                    this.get(HOST).after('visibleChange', this.handle_visible_change, this);
                }
            },

            handle_visible_change: function(e) {
                if (e.newVal) {
                    this.apply_scroll();
                } else {
                    this.revert_scroll();
                }
            },

            /**
             * Remove scroll from window and make the content of the widget/panel scrollable
             */
            apply_scroll: function() {
                if (Lang.isUndefined(OLD_OVERFLOW)) {
                    // Removes scroll from window.
                    OLD_OVERFLOW = this.get(BODY_NODE).getStyle('overflow');
                    this.get(BODY_NODE).setStyle('overflow', 'hidden');

                    // Nasty hack, we have to correct our scroll position if we
                    // change our focus within the modal to a VERY large div
                    // Will need to re-design some UI elements to remove this
                    SCROLLTOP = Y.one('win').get('scrollTop');
                    WIN_SUBSCRIPTION = Y.one('win').after('scroll', function(e) {
                        Y.one('win').set('scrollTop', SCROLLTOP);
                    });

                    this.apply_scroll_to_content();
                }
            },

            /**
             * Add scroll to the panel/widget content
             */
            apply_scroll_to_content: function() {
                var node = undefined;
                if (this.get(HOST) instanceof Y.Panel) {
                    node = this.get(HOST).get('boundingBox').one('.yui3-widget-bd');
                } else if (this.get(HOST) instanceof Y.Widget) {
                    node = this.get(HOST).get('boundingBox');
                }
                if (!Lang.isUndefined(node)) {
                    node.setStyle('maxHeight', this.get('maxHeight'));
                    node.setStyle('overflow', this.get('overflow'));
                }
            },

            /**
             * This restores scroll on the main window
             */
            revert_scroll: function() {
                if (!Lang.isUndefined(OLD_OVERFLOW)) {
                    // Restores scroll.
                    this.get(BODY_NODE).setStyle('overflow', OLD_OVERFLOW);
                    OLD_OVERFLOW = undefined;
                }
                if (!Lang.isUndefined(WIN_SUBSCRIPTION)) {
                    // Kills our event subscription so scrolling works again.
                    WIN_SUBSCRIPTION.detach();
                    WIN_SUBSCRIPTION = undefined;
                }
            }
        },
        {
            NAME: NAME,
            NS: NAME,
            ATTRS: {
                /**
                 * We remove scroll from this temporarily
                 */
                bodyNode: {
                    readyOnly: true,
                    valueFn: function() {
                        return Y.UA.ie > 0 ? Y.one('html') : Y.one('body');
                    }
                },
                /**
                 * Applies this max height to the panel/widget content div
                 */
                maxHeight: { value: '500px', validator: Lang.isString },
                /**
                 * Applies this overflow to the panel/widget content div
                 */
                overflow: { value: 'auto', validator: Lang.isString }
            }
        }
    );

    M.core_outcome = M.core_outcome || {};
    M.core_outcome.scrollpanel = SCROLLPANEL;
}, '@VERSION@', {
    requires: ['plugin', 'widget', 'panel']
});
