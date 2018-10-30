import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'
import beforeAfter from 'before-after.js'

export default StudentView.extend({
    events: {},

    initialize() {},

    render() {
        return this;
    },

    postRender() {
        this.$('.cw-beforeafter-slider').beforeAfter();

        return this;
    }
});
