<template>
    <nav class="bg-purple-600 p-4 text-white shadow-lg top-0 left-0 w-full z-50"> 
      <div class="container mx-auto flex justify-center items-center">
        <router-link to="/" class="text-xl font-bold ">Let's SNAP</router-link>
      </div>
    </nav>
  </template>
  
  <script>
  export default {
    // eslint-disable-next-line vue/multi-word-component-names
    name: 'bar',
    data() {
      return {
        isOpen: false
      };
    },
    methods: {
      toggleMenu() {
        this.isOpen = !this.isOpen;
      }
    }
  };
  </script>
  
  <style scoped>
  @import url('https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

  nav ul li {
    transition: background 0.3s;
  }
  nav {
    position: fixed;
    width: 100%;
    z-index: 1000;
  }
  </style>
  