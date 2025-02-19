<template>
  <div>
    <bar />
    <forgotpass />
  </div>
</template>

<script>
import bar from '@/components/bar.vue';
import forgotpass from '@/components/forgotpass.vue';
export default {
    // eslint-disable-next-line vue/multi-word-component-names
    name : 'Resetpass',
    components : {
        bar,
        forgotpass
    }
    

}
</script>

<style>

</style>