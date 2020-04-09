<template>
    <div class="helpbox">
        <h2 
            class="helpbox-title"
            :class="{unfolded: unfolded}"
            @click="unfolded = !unfolded" 
        >
            {{title}}
        </h2>
        <p class="helpbox-content" v-show="unfolded">{{content}}</p>
    </div>
</template>

<script>
export default {
    name: 'HelpBox',
    props: {
        title: String,
        content: String
    },
    data() {
        return{
            unfolded: false
        }
    }
}
</script>

<style>

</style>