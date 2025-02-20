<template>
    <div class="flex items-center justify-center min-h-screen bg-gray">
      <div class="bg-purple-600 p-6 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold text-center mb-4 text-white">Reset Password</h2>
        <!-- ฟอร์มรีรหัส -->
        <form @submit.prevent="resetPassword">
          <!--ช่องใส่เมล -->
          <div class="mb-4">
            <label class="block text-white text-sm font-medium mb-2" for="email">Email</label>
            <input 
              type="email" 
              v-model="email" 
              placeholder="Email"
              id="email" 
              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400"
            >
          </div>
          <!-- ช่องใส่พิน -->
          <div class="mb-4">
            <label class="block text-white text-sm font-medium mb-2" for="pin">PIN</label>
            <input 
              type="password" 
              v-model="pin" 
              placeholder="Enter PIN"
              id="pin" 
              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400"
            >
          </div>
          <!-- ช่องรหัส -->
          <div class="mb-4">
            <label class="block text-white text-sm font-medium mb-2" for="newpassword">New Password</label>
            <input 
              type="password" 
              v-model="newpassword" 
              placeholder="New Password"
              id="newpassword" 
              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400"
            >
          </div>
          <!-- โชว์ผลตอบรับ -->
          <p v-if="errorMessage" class="text-sm text-center text-red-500">{{ errorMessage }}</p>
          <p v-if="successMessage" class="text-sm text-center text-green-500">{{ successMessage }}</p>
          <!-- ปุ่มส่ง -->
          <button type="submit" class="w-full bg-purple-900 text-white py-2 rounded-lg hover:bg-purple-300 transition">
            Reset Password
          </button>
        </form>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  import router from '@/router';
  
  export default {
    name: "resetPassword",
    data() {
      return {
        email: "",
        pin: "",
        newpassword: "",
        errorMessage: "",
        successMessage: "",
        action : ""
      };
    },
    methods: {
      
      async resetPassword() {
        try {
            const response = await axios.post("http://localhost/socialweb/socialapp/src/server.php", {
            email: this.email,
            pin: this.pin,
            newpassword: this.newpassword,
            action: "resetPassword",  
            }, {
            headers: { "Content-Type": "application/json" }  //กำหนดให้ส่งแบบเจสัน
            });

          
          if (response.data.message === "Password updated successfully") { 
            this.successMessage = response.data.message; //แสดงข้อความตอบกลับ
            this.errorMessage = "";  //ปล่อยให้ช่องนี้ว่าง
            router.push("/");

          } else {
            this.errorMessage = response.data.message; // แสดงข้อความตอบกลับ
            this.successMessage = ""; //ปล่อยให้ช่องนี้ว่าง
          }
        } catch (error) {
          console.error("Error:", error);
          this.errorMessage = error.response?.data?.message || "Errors";
          this.successMessage = "";
        }
      }
    }
  };
  </script>
  