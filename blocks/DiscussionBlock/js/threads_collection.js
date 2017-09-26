define(["backbone", "./thread_model"], function (Backbone, Thread) {

    'use strict';

    var ThreadsCollection = Backbone.Collection.extend({
        model: Thread
    });

    return ThreadsCollection;
});
