<template>
    <li class="course-item">
        <button class="blockmanager-course-item" :data-remote_cid="course.id" :data-remote_name="course.name">
            {{ course.name }}
        </button>
    </li>
</template>

<script>
export default {
    name: 'CourseItem',
    data() {
        return {};
    },
    components: {},
    props: {
        course: Object
    }
};
</script>

<style scoped></style>
