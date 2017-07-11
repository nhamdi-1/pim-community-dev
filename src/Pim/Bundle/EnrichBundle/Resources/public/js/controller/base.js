

import Backbone from 'backbone'
export default Backbone.View.extend({
    active: false,

        /**
         * Render the route given in parameter
         *
         * @param {String} route
         * @param {String} path
         *
         * @return {Promise}
         */
    renderRoute: function () {
        throw new Error('Method renderRoute is abstract and must be implemented!')
    },

    remove: function () {
        this.setActive(false)
    },

    setActive: function (active) {
        this.active = active
    },

        /**
         * Return if wether or not the user can leave the page
         *
         * @return {boolean}
         */
    canLeave: function () {
        var event = {canLeave: true}
        this.trigger('pim:controller:can-leave', event)

        return event.canLeave
    }
})

