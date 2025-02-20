<template>
  <div class="flex items-center justify-center min-h-screen bg-gray">
    <div class="bg-purple-600 p-6 rounded-lg shadow-md w-96">
      <h2 class="text-2xl font-bold text-center mb-4 text-white">Login</h2>
      <!-- ฟอร์มใส่พิน -->
      <form @submit.prevent="login">
        <!-- ช่องใส่เมล -->
        <div class="mb-4">
          <label class="block text-white text-sm font-medium mb-2" for="email">Email</label>
          <input 
          type="email" 
          v-model="email" 
          placeholder="Email"
          id="email" 
          class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
        </div>
        <!-- ช่องใส่รหัส -->
        <div class="mb-4">
          <label class="block text-white text-sm font-medium mb-2" for="password">Password</label>
          <input 
          type="password" 
          v-model="password" 
          placeholder="Password"
          id="password" 
          class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
        </div>
        <!-- โชว์ผลตอบรับ -->
        <p v-if="errorMessage" class="text-sm text-center" style="color: white;">{{ errorMessage }}</p>
        <p v-if="successMessage" class="text-sm text-center text-green-500">{{ successMessage }}</p>
        <!-- ปุ่มพาไปหน้ารีรหัส -->
        <div class="mt-4 text-center mb-4 text-decoration-none">
          <router-link to="/resetPass">
            <button class="text-sm text-purple-200 hover:text-white py-2 px-4 rounded-lg">
              Forgot Password?
            </button>
          </router-link>
        </div>

        <!-- ปุ่มล็อคอิน -->
        <button type="submit" class="w-full bg-purple-900 text-white py-2 rounded-lg hover:bg-purple-300 transition">
          <i class="fa-solid fa-right-to-bracket"></i>
          Login
        </button>
        <div class="mt-4 text-center">
          <p class="text-sm text-white">Don't have an account?</p>
          <router-link to="/register">
            <!-- ปุ่มสมัคสมาชิก -->
            <button class="w-full mt-2 bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition">
              <i class="fa-solid fa-address-card"></i>
              Register
            </button>
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import router from '@/router';

export default {
  name: "loginBody",
  data() {
    return {
      email: "",
      password: "",
      errorMessage: "",
      successMessage: "",
    };
  },
  methods: {

async login() {
    try {
      const response = await axios.post("http://localhost/socialweb/socialapp/src/server.php", {
        email: this.email,
        password: this.password,
        action: "login",  
      }, {
        headers: { "Content-Type": "application/json" } // ส่งไปแบบเจสัน
      });

      if (response.data.message === "Login successful") {
        localStorage.setItem("email", this.email); //เซ็ตค่าเมลไปไว้ในlocal
        localStorage.setItem("token", response.data.token); //เซ็นโทเค่นไปในlocal
        this.successMessage = response.data.message;//โชว์ข้อความสำเร็จ
        this.errorMessage = "";  
        this.email = ""; 
        this.password = "";
        router.push("/home"); //พาไปหน้าหลัก
      } else if (response.data.message.includes("Too many attempts")) {
        this.errorMessage = response.data.message; //โชว์เวลาที่รอ
        this.successMessage = ""; 
      } else {
        this.errorMessage = response.data.message; //โชว์สิ่งผิดพลาด
        this.successMessage = ""; 
      }
    } catch (error) {
      console.error("Error:", error);
      this.errorMessage = error.response?.data?.message || "Errors";
      this.successMessage = "";
    }
  }
  },
};

</script>

<style>
@import '@fortawesome/fontawesome-free/css/all.css';
</style>
