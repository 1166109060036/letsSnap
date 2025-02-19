<template>
    <div>
        <Navbar />
        <Createpost />
        <PostBodyComponent class="postComponent" />
    </div>
</template>

<script>
import Navbar from '../components/Navbar.vue';
import PostBodyComponent from '@/components/Postbody.vue';
import Createpost from '@/components/createpost.vue';

export default {
    name: 'HomePage',
    components: {
        Navbar,
        PostBodyComponent,
        Createpost
    }
}
</script>

<style>

</style>