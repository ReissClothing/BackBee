/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 */

define(
    [
        'Core',
        'Core/Renderer',
        'jquery',
        'content.models.Content',
        'content.models.ContentSet',
        'definition.manager',
        'content.container',
        'content.repository',
        'text!content/tpl/dropzone',
        'component!mask',
        'jsclass'
    ],
    function (Core,
              Renderer,
              jQuery,
              Content,
              ContentSet,
              DefinitionManager,
              ContentContainer,
              ContentRepository,
              dropZoneTemplate
            ) {

        'use strict';

        var ContentManager = new JS.Class({

            contentClass: 'bb-content',
            identifierDataAttribute: 'bb-identifier',
            dropZoneClass: 'bb-dropzone',
            idDataAttribute: 'bb-id',
            droppableClass: '.bb-droppable',
            imageClass: 'Element/Image',
            defaultPicturePath: require.toUrl('html/img/filedrop.png'),
            contentSelectedClass: 'bb-content-selected',


            initialize: function () {
                this.maskMng = require('component!mask').createMask({});
            },
            /**
             * Search all contentset with dragzone="true" attribute
             * and dont have data-bb-id attribute for build element
             */
            buildContentSet: function () {
                var self = this,
                    dropzone = jQuery(this.droppableClass).not('[data-' + this.idDataAttribute + ']');

                dropzone.each(function () {
                    var currentTarget = jQuery(this);

                    if (currentTarget.hasClass(self.contentClass)) {
                        ContentContainer.addContent(self.getContentByNode(currentTarget));
                    }
                });
            },

            addDefaultZoneInContentSet: function (active) {
                jQuery('.' + this.dropZoneClass).remove();

                if (active === false) {
                    return;
                }

                this.buildContentSet();

                var contentSets = ContentContainer.findContentSetByAccept(),
                    div,
                    contentSet,
                    hasChildren,
                    key;

                for (key in contentSets) {
                    if (contentSets.hasOwnProperty(key)) {
                        contentSet = contentSets[key];
                        hasChildren = contentSet.jQueryObject.children().not('.content-actions').length > 0;

                        if (hasChildren === false) {
                            div = Renderer.render(dropZoneTemplate, {'class': this.dropZoneClass, 'type': contentSet.getLabel()});
                            contentSet.jQueryObject.append(div);
                        }
                    }
                }
            },

            isUsable: function (type) {
                var contents = Core.config('unclickable_contents:contents'),
                    result = true;

                if (contents !== undefined) {
                    if (contents.indexOf(type) !== -1) {
                        result = false;
                    }
                }

                return result;
            },

            /**
             * Create new element from the API
             * @param {String} type
             * @returns {Promise}
             */
            createElement: function (type) {
                var self = this,
                    dfd = jQuery.Deferred();

                ContentRepository.save({'type': type}).done(function (data, response) {
                    dfd.resolve(self.buildElement({'type': type, 'uid': response.getHeader('BB-RESOURCE-UID')}));

                    return data;
                });

                return dfd.promise();
            },

            /**
             * Build a content/contentSet element according to the definition
             * @param {Object} event
             * @returns {Object}
             */
            buildElement: function (config) {
                var content,
                    objectIdentifier = this.buildObjectIdentifier(config.type, config.uid),
                    element = jQuery('[data-' + this.identifierDataAttribute + '="' + objectIdentifier + '"]'),
                    allowedAttributes = [],
                    key;

                if (objectIdentifier !== undefined) {

                    content = ContentContainer.findByUid(config.uid);

                    if (null === content) {

                        config.definition = DefinitionManager.find(config.type);
                        config.jQueryObject = element;

                        if (config.definition !== null) {
                            if (config.definition.properties.is_container) {
                                content = new ContentSet(config);
                            } else {
                                content = new Content(config);
                            }
                        }

                        ContentContainer.addContent(content);
                    } else {
                        content.jQueryObject = element;
                        content.populate();
                    }

                    if (undefined !== config.elementData) {
                        if (undefined === content.data) {
                            allowedAttributes = [
                                'elements',
                                'extra',
                                'image',
                                'label',
                                'parameters',
                                'type',
                                'uid'
                            ];

                            content.data = {};
                            for (key in config.elementData) {
                                if (config.elementData.hasOwnProperty(key)) {
                                    if (-1 !== allowedAttributes.indexOf(key)) {
                                        content.data[key] = config.elementData[key];
                                    }
                                }
                            }
                        }
                    }
                }

                return content;
            },

            /**
             * Remove the content from dom and Content container
             * and change state of parent to updated
             * @param {Object} content
             * @returns {undefined}
             */
            remove: function (content) {
                if (typeof content === 'object') {
                    var parent = content.getParent();

                    if (typeof parent === 'object') {
                        parent.setUpdated(true);
                    }

                    content.jQueryObject.remove();
                    ContentContainer.remove(content);

                    parent.select();

                    this.addDefaultZoneInContentSet(true);
                }
            },

            replaceWith: function (oldContent, newContent) {
                /* elements */
                var elementInfos = {},
                    renderModeParam =  oldContent.getParameters('rendermode'),
                    renderMode = (renderModeParam !== undefined) ? renderModeParam.value : undefined,
                    oldContentParent = oldContent.getParent(),
                    oldContentHtml = oldContent.jQueryObject,
                    self = this;
                this.maskMng.mask(oldContentHtml);
                newContent.getHtml(renderMode).done(function (html) {
                    jQuery(oldContentHtml).replaceWith(html);
                    self.computeImages();
                    oldContentParent.getData("elements").done(function (elements) {
                        jQuery.each(elements, function (key, data) {
                            if (data.uid === oldContent.uid) {
                                elementInfos[key] = { uid: newContent.uid, type: newContent.type };
                                return true;
                            }
                        });
                        oldContentParent.setElements(elementInfos);
                    }).always(function () {
                        self.maskMng.unmask(oldContentHtml);
                    });
                }).always(function () {
                    self.maskMng.unmask(oldContentHtml);
                });
            },


            /**
             * Retrieve a content by a node (jQuery)
             * @param {Object} node
             * @returns {Mixed}
             */
            getContentByNode: function (node) {
                var identifier = node.data(this.identifierDataAttribute),
                    result;

                if (null !== identifier) {
                    result = this.buildElement(this.retrievalObjectIdentifier(identifier), true);
                }

                return result;
            },

            /**
             * Retrieve a object identifier for split uid and type
             */
            retrievalObjectIdentifier: function (objectIdentifier) {
                var regex,
                    object = {},
                    res;

                if (objectIdentifier) {
                    /*jslint regexp: true */
                    regex = /(.+)\(([a-f0-9]+)\)$/;
                    /*jslint regexp: false */

                    res = regex.exec(objectIdentifier);

                    if (null !== res) {
                        object.type = res[1];
                        object.uid = res[2];
                    }
                }

                return object;
            },

            refreshImages: function (html) {
                html = jQuery(html);

                var images = html.find('img'),
                    refreshPicture = function (img) {
                        var src = img.attr('src');

                        if (src.length === 0 || img.naturalWidth === 0) {
                            src = require('content.manager').defaultPicturePath;

                        }

                        img.attr('src', src + '?' + new Date().getTime());
                    };

                if (images.length > 0) {
                    images.each(function () {
                        refreshPicture(jQuery(this));
                    });
                }

                if (html.get(0) && html.get(0).tagName === 'IMG') {
                    refreshPicture(html);
                }

                return html;
            },

            computeImages: function (selector) {

                selector = selector || '';

                var self = this,
                    images = jQuery(selector + ' [data-' + this.identifierDataAttribute + '^="' + this.imageClass + '"]');

                images.each(function () {
                    var image = jQuery(this);

                    if (image.context.naturalWidth === 0) {
                        image.attr('src', self.defaultPicturePath);
                    }
                });
            },

            unSelectContent: function () {
                var currentSelected = jQuery('.' + this.contentSelectedClass),
                    currentContent;

                if (currentSelected.length > 0) {
                    currentContent = ContentContainer.find(currentSelected.data(this.idDataAttribute));
                    currentContent.unSelect();
                }
            },

            /**
             * Build an object identifier
             * @param {String} type
             * @param {String} uid
             * @returns {null|String}
             */
            buildObjectIdentifier: function (type, uid) {
                var objectIdentifier = null;

                if (typeof type === 'string' && typeof uid === 'string') {
                    objectIdentifier = type + '(' + uid + ')';
                }

                return objectIdentifier;
            }
        });

        return new JS.Singleton(ContentManager);
    }
);
