define(function () {
    'use strict';

    var Notify = {
        boxTemplate: '<p></p><div class="bb-box-progress"></div>',

        getBox: function () {
            var wrapper = window.document.createElement('div');
            wrapper.className = 'bb-box-notify';
            wrapper.innerHTML = this.boxTemplate;
            return wrapper;
        },

        clearNotification: function (wrapper, box) {
            wrapper.removeChild(box);
        },

        printNotification: function (type, text) {
            var wrapper = window.document.getElementById('bb-box-wrapper'),
                box = this.getBox(),
                content = box.getElementsByTagName('p');

            if (null === wrapper) {
                return;
            }

            box.className = box.className + ' ' + type;
            content = content[0];
            content.innerHTML = text;
            wrapper.appendChild(box);
            setTimeout(this.clearNotification, 5000, wrapper, box);
        },

        success: function (text) {
            this.printNotification('success', text);
        },

        warning: function (text) {
            this.printNotification('warning', text);
        },

        error: function (text) {
            this.printNotification('error', text);
        }
    };

    return {
        create: function () {
            return Notify.printNotification.apply(Notify, arguments);
        },
        success: function () {
            return Notify.success.apply(Notify, arguments);
        },
        warning: function () {
            return Notify.warning.apply(Notify, arguments);
        },
        error: function () {
            return Notify.error.apply(Notify, arguments);
        }
    };

});