<template>
    <li class="semester-item">
        <p class="semester-description">{{ semester_name }}</p>
        <ul class="course-list">
            <CourseItem v-for="course in courses" :key="course.id" :course="course" />
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
    }
};
</script>

<style scoped></style>
