<template>
  <div class="flex items-center justify-center h-screen ">
    <div class="bg-purple-600 p-6 rounded-lg shadow-md w-96">
      <h2 class="text-2xl font-bold text-center mb-4 text-white">Register</h2>
      <!-- ฟอร์มสมัคสมาชิก -->
      <form @submit.prevent="register">
        <!-- ช่องใส่เมล -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2 text-white" for="email">Email</label>
          <input 
            type="text" 
            id="email" 
            v-model="email"
            placeholder="Enter your email" 
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
        </div>
        <!-- ช่องใส่รหัส -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2 text-white" for="password">Password</label>
          <input 
            type="password" 
            id="password" 
            v-model="password"
            placeholder="Enter your password"
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            <p v-if="passwordStrengthMessage" class="text-sm text-red-400">{{ passwordStrengthMessage }}</p>
          </div>
          <!-- ช่องใส่รหัสอีกครั้ง -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2 text-white" for="confirmPassword">Password Confirm</label>
          <input 
            type="password" 
            id="confirmPassword" 
            v-model="confirmPassword"
            placeholder="Enter your password again."
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
        </div>
        <!-- ช่องใส่พิน -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2 text-white" for="confirmPassword">Pin</label>
          <input 
            type="password" 
            id="pin" 
            v-model="pin"
            placeholder="Enter your pin"
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            <p v-if="pinStrengthMessage" class="text-sm text-red-400">{{ pinStrengthMessage }}</p>
          </div>
          <!-- แสดงข้อความตอบกลับ -->
        <p v-if="errorMessage" class="text-sm text-center" style="color: white;">{{ errorMessage }}</p>
        <p v-if="successMessage" class="text-sm text-center text-green-500">{{ successMessage }}</p>
        <!-- ปุ่มสมัคร -->
        <button type="submit" class="w-full bg-purple-900 text-white py-2 rounded-lg hover:bg-purple-500 transition">
          <i class="fa-solid fa-address-card"></i>
          Register
        </button>
      </form>
      <!-- ปุ่มสำหรับคนมีไอดีอยู่แล้วก็กดเข้าตรงนี้ -->
      <div class="mt-4 text-center">
        <p class="text-sm text-white">Already have an account?</p>
        <router-link to="/">
          <button class="w-full mt-2 bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition">
            <i class="fa-solid fa-right-to-bracket"></i>
            Login
          </button>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
import router from '@/router';
import axios from 'axios';


export default {
  name: "registerBody",
  data() {
    return {
      email: "",
      password: "",
      confirmPassword: "",
      pin: "", 
      errorMessage: "",
      successMessage: "",
      passwordStrengthMessage: '',
      pinStrengthMessage: '',
    };
  },
  watch: { //ตัวที่ไว้ตรวจสอบความแข็งแรงของพินกับพาส
    password(newPassword) {
      this.validatePasswordStrength(newPassword);
    },
    pin(newpin){
      this.validatePinStrength(newpin);
    }
  },
  methods: {
    validatePasswordStrength(password) { //เงื่อนไขการตั้งพาส
      if (password.length < 8) {
        this.passwordStrengthMessage = "Password must be at least 8 characters long.";
      } else if (!/[A-Z]/.test(password)) {
        this.passwordStrengthMessage = "Password must contain at least one uppercase letter.";
      } else if (!/[a-z]/.test(password)) {
        this.passwordStrengthMessage = "Password must contain at least one lowercase letter.";
      } else if (!/[0-9]/.test(password)) {
        this.passwordStrengthMessage = "Password must contain at least one number.";
      } else if (!/[!@#$%^&*]/.test(password)) {
        this.passwordStrengthMessage = "Password must contain at least one special character (!@#$%^&*).";
      } else {
        this.passwordStrengthMessage = "";
      }
    },
    validatePinStrength(pin){ //เงื่อนไขการตั้งพิน
      if(pin.length>6){
        this.pinStrengthMessage = "PIN must be less than 6 in length."
      }else {
        this.pinStrengthMessage = "";
      }
    },
    async register() { //สมัคสมาชิก
      if (this.password !== this.confirmPassword) { //ถ้ารหัสผ่านสองช่องไม่ตรงกันจะสมัคไม่ได้
        this.errorMessage = "Passwords do not match!";
        this.successMessage = "";
        return;
      }

      if (this.passwordStrengthMessage) { //ถ้ารหัสผ่านไม่แข็งแรงจะะสมัคไม่ได้
        this.errorMessage = "Please use a stronger password.";
        return;
      }
      if (this.pinStrengthMessage) { //ถ้าพินเกิน6ตัวจะส่งไม่ได้
        this.errorMessage = "Please enter a PIN that is 6 digits long.";
        return;
      }


      try { //ส่งข้อมูลไปฐานข้อมูล
        const response = await axios.post(
          "http://localhost/socialweb/socialapp/src/server.php",
          {
            email: this.email,
            password: this.password,
            pin: this.pin, // ส่งค่า pin ไปที่เซิร์ฟเวอร์
            action: "register",
          }
        );

        console.log("Server Response:", response.data);

        if (response.data.message === "User registered successfully") { //ถ้าส่งสำเร็จ
          localStorage.setItem("email", this.email); //เก็บเมลไป local
          localStorage.setItem("token", response.data.token || ""); //เก็บโทเค่นไป local
          this.successMessage = response.data.message;
          this.errorMessage = "";
          this.email = "";
          this.password = "";
          this.confirmPassword = "";
          this.pin = "";
          router.push("/profilesetup");
        } else {
          this.errorMessage = response.data.message;
          this.successMessage = "";
        }
      } catch (error) {
        console.error("Error:", error);
        this.errorMessage = error.response?.data?.message || "Error during registration";
        this.successMessage = "";
      }
    },
  },
};
</script>

<style>
@import '@fortawesome/fontawesome-free/css/all.css';
</style>
