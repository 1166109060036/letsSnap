<template>
    <div>
        <Navbar />
        <massagebody />
    </div>
</template>

<script>
import Navbar from '../components/Navbar.vue';
import massagebody from '@/components/massagebody.vue';

export default {
    name: 'MessageView',
    components: {
        Navbar,
        massagebody
    }
}
</script>

<style>

</style>