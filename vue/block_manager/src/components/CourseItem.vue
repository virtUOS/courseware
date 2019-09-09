<template>
    <li class="course-item">
        <button
            class="blockmanager-course-item"
            :data-remote_cid="course.id"
            :data-remote_name="course.name"
            @click="
                $emit('course-selected', {
                    remoteName: course.name,
                    remoteId: course.id
                })
            "
        >
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
    },
    methods: {}
};
</script>

<style scoped></style>
