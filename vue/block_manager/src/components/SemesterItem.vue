<template>
    <li class="semester-item">
        <p class="semester-description" @click="toggleList">{{ semester_name }}</p>
        <ul class="course-list">
            <CourseItem v-for="course in courses" :key="course.id" :course="course" @course-selected="courseSelected" />
        </ul>
    </li>
</template>

<script>
import CourseItem from './CourseItem.vue';
export default {
    name: 'SemesterItem',
    components: {
        CourseItem
    },
    props: {
        courses: Array,
        semester_name: String
    },
    methods: {
        courseSelected(event) {
            this.$emit('course-selected', event);
        },
        toggleList(event) {
            $(event.target)
                .siblings('ul')
                .toggle();
            if (!$(event.target).hasClass('unfolded')) {
                $(event.target).addClass('unfolded');
            } else {
                $(event.target).removeClass('unfolded');
            }
        }
    }
};
</script>

<style scoped></style>
