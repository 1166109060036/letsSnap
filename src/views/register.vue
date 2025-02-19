<template>
  <div>
    <bar />
    <registerbody />
  </div>
</template>

<script>
import bar from '@/components/bar.vue';
import registerbody from '@/components/registerbody.vue';
export default {
    name: 'RegisterPage',
    components:{
        bar,
        registerbody
    }

}
</script>

<style>

</style>