<template>
    <li class="semester-item">
        <p class="semester-description" :class="{ unfolded: unfolded }" @click="toggleList">
            {{ semester_name }}
        </p>
        <ul v-if="unfolded" class="course-list">
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
    data() {
        return {
            unfolded: false
        };
    },
    methods: {
        courseSelected(event) {
            this.$emit('course-selected', event);
        },
        toggleList() {
            this.unfolded = !this.unfolded;
        }
    }
};
</script>

<style scoped></style>
