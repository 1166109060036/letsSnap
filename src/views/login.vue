<template>
    <div>
        <bar /> 
        <Loginbody />
    </div>
</template>

<script>
import bar from '../components/bar.vue';
import Loginbody from '../components/loginbody.vue';
export default {
    name: "LoginPage",
    components:{
        bar
        ,Loginbody
    }
}
</script>

<style>
</style>