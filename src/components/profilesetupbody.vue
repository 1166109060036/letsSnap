<template>
  <div class="flex items-center justify-center h-screen">
    <div class="bg-purple-600 p-6 rounded-lg shadow-md w-96">
      <h2 class="text-2xl font-bold text-center mb-4 text-white">Profile Setup</h2>
      <!-- ฟอร์มแก้ไขโปรไฟล์ -->
      <form @submit.prevent="saveProfile">
        <!-- แก้ไขเมล -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2 text-white" for="email">Email</label>
          <input 
            type="text" 
            id="email" 
            v-model="email"
            readonly
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400"
          />
        </div>
        <!-- แก้ไขชื่อ --> 
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2 text-white" for="firstName">First Name</label>
          <input 
            type="text" 
            id="firstName" 
            v-model="firstName"
            placeholder="First Name" 
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400"
          />
        </div>
        <!-- แก้ไขนามสกุล -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2 text-white" for="lastName">Last Name</label>
          <input 
            type="text" 
            id="lastName" 
            v-model="lastName"
            placeholder="Last Name" 
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400"
          />
        </div>
        <!-- ช่องสำหรับอัพโหลดรูปาพ -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2 text-white" for="profilePicture">Profile Picture</label>
          <input 
            type="file" 
            id="profilePicture" 
            @change="handleFileUpload"
            accept="image/*"
            class="w-full px-3 py-2 border rounded-lg bg-white text-black focus:outline-none focus:ring-2 focus:ring-purple-400"
          />
        </div>

        <!-- โชว์ตัวอย่างรูปที่อัพโหลด -->
        <div v-if="imagePreview" class="mb-4 text-center">
          <img :src="imagePreview" alt="Profile Preview" class="w-32 h-32 object-cover rounded-full mx-auto border-2 border-white shadow-lg">
        </div>
        <!-- แสดงข้อความตอบกลับ -->
        <p v-if="errorMessage" class="text-sm text-center text-red-500">{{ errorMessage }}</p>
        <p v-if="successMessage" class="text-sm text-center text-green-500">{{ successMessage }}</p>
        <!-- ปุ่มแก้ไขโปรไฟล์ -->
        <button type="submit" class="w-full bg-purple-900 text-white py-2 rounded-lg hover:bg-purple-500 transition">
          <i class="fa-solid fa-save"></i>
          Save Profile
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import router from "@/router";
import axios from "axios";

export default {
  name: "ProfileSetup",
  data() {
    return {
      email: localStorage.getItem("email") || "",
      firstName: "",
      lastName: "",
      profilePicture: null,
      imagePreview: null, // เก็บ URL รูปที่เลือก
      errorMessage: "",
      successMessage: "",
      token: localStorage.getItem("token")
    };
  },
  async mounted(){ 

      if (!this.token) { //ถ้าไม่มีเมลก็จะส่งไปหน้าแรก
        console.error("User token not found");
        router.push("/"); 
      }

    },
  methods: {
    handleFileUpload(event) { //โชว์ไฟล์ที่อัพโหลด
      const file = event.target.files[0];
      if (file) { //ถ้ามีไฟล์อัพโหลด
        if (file.size > 2 * 1024 * 1024) { // จำกัดขนาด 2MB
          this.errorMessage = "File size must be less than 2MB.";
          return;
        }
        this.profilePicture = file;
        this.imagePreview = URL.createObjectURL(file);
      }
    },
    //บันทึกโปรไฟล์
    async saveProfile() {
      if (!this.firstName || !this.lastName || !this.profilePicture) { //ถ้าไม่มีทั้งสามช่องจะไม่อัพเดท
        this.errorMessage = "Please fill in all fields.";
        return;
      }

      let formData = new FormData(); //สร้างเพื่อรอส่งข้อมูล
      //เอาพวกนี้เก็บลง formdata
      formData.append("action", "saveProfile"); 
      formData.append("email", this.email); 
      formData.append("firstName", this.firstName);
      formData.append("lastName", this.lastName);
      formData.append("profilePicture", this.profilePicture); 

      try { //ส่งที่เก็บไว้ไปฐานข้อมูล 
        const response = await axios.post("http://localhost/socialweb/socialapp/src/server.php", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });

        this.successMessage = response.data.message; 
        this.errorMessage = "";

        // รีเซ็ตฟอร์มหลังจากบันทึกข้อมูล
        this.firstName = "";
        this.lastName = "";
        this.profilePicture = null;
        this.imagePreview = null;
        router.push("/home"); // เปลี่ยนหน้าไปยังหน้า home
      } catch (error) {
        console.error("❌ API Error:", error.response?.data || error);
        this.errorMessage = error.response?.data?.message || "Error saving profile.";
        this.successMessage = "";
      }
    },
  },
};
</script>



<style>
@import '@fortawesome/fontawesome-free/css/all.css';
</style>
