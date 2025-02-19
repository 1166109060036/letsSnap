import { createRouter, createWebHistory } from "vue-router";
import Home from "../views/Home.vue";
import login from "../views/login.vue";
import register from "@/views/register.vue";
import profilesetup from "@/views/profilesetup.vue";
import message from "@/views/message.vue";
import Resetpass from "@/views/resetpass.vue";

const routes = [
  { path: "/", component: login },
  { path: "/home", component: Home },
  { path: "/register", component: register },
  { path: "/profilesetup", component: profilesetup },
  { path: "/message", component: message },
  { path: "/resetpass", component: Resetpass},
]; 

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL), 
  routes,
});

export default router;
