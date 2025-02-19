<template>
  <div>
    <bar />
    <profilesetupbody />
  </div>
</template>

<script>
import bar from '../components/bar.vue';
import profilesetupbody from '@/components/profilesetupbody.vue';
export default {
    name: "profileSetup",
    components:{
        bar
        ,profilesetupbody
    }

}
</script>

<style>

</style>