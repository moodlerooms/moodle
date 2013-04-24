/**
 * Alert Badge
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package message_badge
 * @author Mark Nielsen
 */

/**
 * @namespace M.message_badge
 */
M.message_badge = M.message_badge || {};

/**
 * Determine if we have done init yet
 */
M.message_badge.initDone = false;

/**
 * Holds the main alert list overlay
 */
M.message_badge.alertListOverlay = undefined;

/**
 * Holds the message overlay (AKA current message being read)
 */
M.message_badge.messageOverlay = undefined;

/**
 * Holds the ID of the message container that is currently being read
 */
M.message_badge.activeMessageId = undefined;

/**
 * Holds the URL to mark a message read before going to the real URL
 */
M.message_badge.forwardURL = undefined;

/**
 * Init Badge
 *
 * @param {YUI} Y
 */
M.message_badge.init_badge = function(Y, forwardURL) {
    var badge = Y.one('.message_badge');

    if (!badge) {
        return;  // Doesn't exist
    }
    badge.removeClass('message_badge_hidden');

    // Save for later
    M.message_badge.forwardURL = forwardURL;

    // Define behavior for showing/hiding the overlays
    badge.on('click', function(event) {
        M.message_badge.init_overlay(Y, function() {
            var overlay = M.message_badge.alertListOverlay;

            if (!overlay.get('visible')) {
                overlay.set('align', M.message_badge.align_alert_list_overlay(Y, badge));
                overlay.show();
                event.stopPropagation();

                var subscriber = Y.delegate('click', function(e) {
                    if (overlay.get('visible') && !e.target.ancestor('.message_badge_container') && !e.target.ancestor('#helppopupbox')) {
                        overlay.hide();
                        M.message_badge.hide_message_overlay(Y);
                        subscriber.detach();
                    }
                }, 'body', 'body');
            }
        });
    });
};

/**
 * Init the overlay
 *
 * @param Y
 */
M.message_badge.init_overlay = function(Y, callback) {
    if (M.message_badge.initDone) {
        if (typeof callback == 'function') {
            callback();
        }
        return;
    }
    M.message_badge.initDone = true;

    M.message_badge.get_messages_html(Y, function() {
        var badge       = Y.one('.message_badge');
        var container   = Y.one('.message_badge_container');
        var overlayNode = Y.one('.message_badge_overlay');

        // Move the container as a child of the body
        // Ensures that our overlay sits on top of everything else
        container.appendTo(Y.one('body'));

        // We must make visible before rendering - messes up positioning
        overlayNode.removeClass('message_badge_hidden');

        // Create our main overlay
        var overlay = new Y.Overlay({
            srcNode: overlayNode,
            visible: false,
            zIndex: 1001,
            align: M.message_badge.align_alert_list_overlay(Y, badge)
        });
        overlay.render();

        // Save for later
        M.message_badge.alertListOverlay = overlay;

        // Process all of the messages
        overlay.get('srcNode').all('.message_badge_message').each(function(node) {
            M.message_badge.init_message(Y, node);
        });

        // Activate help icons
        M.message_badge.init_help_icons(Y);

        if (typeof callback == 'function') {
            callback();
        }
    });
};

/**
 * Attach behavior to a message
 *
 * @param Y
 * @param messageNode
 */
M.message_badge.init_message = function(Y, messageNode) {
    messageNode.all('.message_badge_contexturl').on('click', function(e) {
        M.message_badge.forward(Y, messageNode, e);
    });
    messageNode.all('.message_badge_message_text a').on('click', function(e) {
        M.message_badge.forward(Y, messageNode, e);
    });
    messageNode.one('.message_badge_ignoreurl').on('click', function(e) {
        e.preventDefault();

        if (messageNode.get('id') == M.message_badge.activeMessageId) {
            M.message_badge.hide_message_overlay(Y);
        } else {
            M.message_badge.ignore_message(Y, messageNode.one('.message_badge_ignoreurl').get('href'));
            messageNode.addClass('message_badge_hidden');
        }
    });
    messageNode.one('.message_badge_readurl').on('click', function(e) {
        e.preventDefault();

        if (messageNode.get('id') != M.message_badge.activeMessageId) {
            M.message_badge.hide_message_overlay(Y);

            var overlay = new Y.Overlay({
                visible: false,
                zIndex: 1000
            });

            M.message_badge.populate_overlay(Y, overlay, messageNode.one('.message_badge_readurl').get('href'), function(unreadCount) {
                overlay.get('srcNode').addClass('message_badge_message_overlay');
                messageNode.addClass('dimmed_text message_badge_message_opened');
                M.message_badge.update_unread_count(Y, unreadCount);

                overlay.get('srcNode').one('.message_badge_message_close a').on('click', function(e) {
                    e.preventDefault();
                    M.message_badge.hide_message_overlay(Y);
                });
                overlay.after('visibleChange', function (e) {
                    if (!overlay.get('visible')) {
                        messageNode.addClass('message_badge_hidden');
                    }
                });
                if (M.message_badge.messageOverlay != undefined) {
                    M.message_badge.messageOverlay.destroy();
                    M.message_badge.messageOverlay = undefined;
                }
                M.message_badge.messageOverlay  = overlay;
                M.message_badge.activeMessageId = messageNode.get('id');

                // Not really sure, but have to align here and show for the align to work properly... gah
                overlay.set('align', M.message_badge.align_message_overlay(Y));
                overlay.show();
            });
        }
    });
};

/**
 * Depending on which side of the window the node is on, align in the most appropriate direction
 *
 * @param Y
 * @param node
 */
M.message_badge.align_alert_list_overlay = function (Y, node) {
    var centerX = (node.get('winWidth') / 2);
    var centerY = (node.get('winHeight') / 2);
    var x       = node.getX();
    var y       = node.getY();
    var points;

    if (x <= centerX && y <= centerY) {        // It's in top left quadrant
        points = [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL];

    } else if (x >= centerX && y <= centerY) { // It's in top right quadrant
        points = [Y.WidgetPositionAlign.TR, Y.WidgetPositionAlign.BR];

    } else if (x <= centerX && y >= centerY) { // It's in bottom left quadrant
        points = [Y.WidgetPositionAlign.BL, Y.WidgetPositionAlign.TL];

    } else if (x >= centerX && y >= centerY) { // It's in bottom right quadrant
        points = [Y.WidgetPositionAlign.BR, Y.WidgetPositionAlign.TR];
    }
    return {
        node: node,
        points: points
    };
};

/**
 * Depending on which side of the window the badge is on, align on
 * correct side of the alert list overlay
 *
 * @param Y
 */
M.message_badge.align_message_overlay = function (Y) {
    var badge   = Y.one('.message_badge');
    var centerX = (badge.get('winWidth') / 2);
    var x       = badge.getX();
    var points;

    if (x <= centerX) { // It's in the left half
        points = [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.TR]

    } else {            // It's in the right half
        points = [Y.WidgetPositionAlign.TR, Y.WidgetPositionAlign.TL];
    }
    return {
        node: M.message_badge.alertListOverlay.get('srcNode'),
        points: points
    };
};

/**
 * Hide and destroy the message overlay
 *
 * @param Y
 */
M.message_badge.hide_message_overlay = function(Y) {
    if (M.message_badge.messageOverlay != undefined) {
        M.message_badge.messageOverlay.hide();
    }
};

/**
 * Send a request to ignore a message (AKA mark as read) and
 * update the badge count
 *
 * @param Y
 * @param url
 */
M.message_badge.ignore_message = function(Y, url) {
    Y.io(url, {
        on: {
            success: function(id, o) {
                var response = Y.JSON.parse(o.responseText);

                if (response.error != undefined) {
                    alert(response.error);
                } else {
                    M.message_badge.update_unread_count(Y, response.args);
                }
            },
            failure: function(id, o) {
                alert(M.str.message_badge.genericasyncfail);
            }
        }
    });
};

/**
 * Used to update the badge count
 *
 * @param Y
 * @param unreadCount
 */
M.message_badge.update_unread_count = function(Y, unreadCount) {
    if (unreadCount >= 1) {
        Y.one('.message_badge_count').set('innerHTML', unreadCount);
    } else {
        Y.one('.message_badge_count').remove();
        Y.one('.message_badge_empty').removeClass('message_badge_hidden');
    }
};

/**
 * Some links take us off page by clicking on them, first mark the
 * message that they belong to as read, then redirect to their URL
 *
 * @param Y
 * @param messageNode
 * @param e
 */
M.message_badge.forward = function(Y, messageNode, e) {
    var aNode = null;
    if (e.target.test('a')) {
        aNode = e.target;
    } else {
        aNode = e.target.ancestor('a');
    }
    if (aNode !== null) {
        // Go to our page first to mark the message read, it'll then forward to the actual URL
        aNode.set('href',
            M.message_badge.forwardURL +
            '&messageid=' + encodeURIComponent(messageNode.getAttribute('messageid')) +
            '&url=' + encodeURIComponent(aNode.get('href'))
        );

        // We do this just in case the URL opens a new window (prevent, bad behavior)
        aNode.target.removeAttribute('target');
    }
};

/**
 * Populates a overlay with information found at endpoint
 *
 * @param Y
 * @param url
 */
M.message_badge.populate_overlay = function(Y, overlay, url, onsuccess) {
    Y.io(url, {
        on: {
            success: function(id, o) {
                var response = Y.JSON.parse(o.responseText);

                if (response.error != undefined) {
                    alert(response.error);
                } else {
                    if (response.header != undefined) {
                        overlay.set("headerContent", response.header);
                    }
                    if (response.body != undefined) {
                        overlay.set("bodyContent", response.body);
                    }
                    if (response.footer != undefined) {
                        overlay.set("footerContent", response.footer);
                    }
                    overlay.render('.message_badge_container');

                    if (typeof onsuccess == 'function') {
                        if (response.args != undefined) {
                            onsuccess(response.args);
                        } else {
                            onsuccess();
                        }
                    }
                }
            },
            failure: function(id, o) {
                alert(M.str.message_badge.genericasyncfail);
            }
        }
    });
};

/**
 * Fetch messages HTML and add it to the DOM
 *
 * @param Y
 */
M.message_badge.get_messages_html = function(Y, onsuccess) {
    Y.io(M.cfg.wwwroot + '/message/output/badge/view.php?controller=ajax&action=getmessages', {
        on: {
            success: function(id, o) {
                var response = Y.JSON.parse(o.responseText);

                if (response.error != undefined) {
                    alert(response.error);
                } else {
                    Y.one('.message_badge').get('parentNode').append(response.messages);

                    if (typeof onsuccess == 'function') {
                        onsuccess();
                    }
                }
            },
            failure: function() {
                alert(M.str.message_badge.genericasyncfail);
            }
        }
    });
};

/**
 * Make Moodle help icons active that come back through JSON response
 *
 * @param Y
 */
M.message_badge.init_help_icons = function(Y) {
    Y.all('.message_badge_overlay .helplink a').each(function(node) {
        // Prevent re-processing help icons
        if (!node.hasClass('message_badge_helpicon_processed')) {
            node.addClass('message_badge_helpicon_processed');

            M.util.help_icon.add(Y, {
                id: node.get('id'),
                url: node.get('href')
            })
        }
    });
};
