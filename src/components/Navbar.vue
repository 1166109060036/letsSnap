<template>
  <nav class="bg-purple-600 p-4 text-white shadow-lg top-0 left-0 w-full z-50">
    <div class="container mx-auto flex justify-between items-center">

      <!-- โชว์โปรไฟล์และข้อมูลผู้ใช้ -->
      <div class="flex items-center space-x-4">
        <!-- รูปโปรไฟล์ -->
        <img
          :src="profilePicture"
          alt="Profile Picture"
          class="w-16 h-16 rounded-full border-4 border-purple-600"
        />
        <!-- ชื่อกับเมล -->
        <div>
          <h2 class="text-xl font-bold">{{ firstName }} {{ lastName }}</h2>
          <p class="text-white-600 text-sm">{{ email }}</p>
        </div>
      </div>

      <!-- ปุ่มสำหรับกดโหมดโทรศัพท์ -->
      <button class="md:hidden" @click="toggleMenu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
        </svg>
      </button>

      <!-- เมนู -->
      <ul :class="{'block': isOpen, 'hidden': !isOpen}" class="md:flex md:space-x-6 md:items-center md:static md:bg-transparent md:w-auto md:p-0 bg-purple-600 md:flex-row absolute w-full top-16 left-0 p-4 mt-5">
        <li><router-link to="/home" class="block py-2 px-4 hover:bg-purple-500 rounded">Home</router-link></li>
        <li><router-link to="/message" class="block py-2 px-4 hover:bg-purple-500 rounded">Messages</router-link></li>
        <li><router-link to="/profilesetup" class="block py-2 px-4 hover:bg-purple-500 rounded">Edit Profile</router-link></li>

        <!-- ปุ่มออกระบบ -->
        <li class="flex justify-center w-full md:w-auto">
          <button @click="logout" class="block py-2 px-4 hover:bg-purple-500 rounded">Log Out</button>
        </li>
      </ul>

    </div>
  </nav>
</template>

<script>
import router from '@/router';
import axios from 'axios';

export default {
  // eslint-disable-next-line vue/multi-word-component-names
  name: 'Navbar',
  data() {
    return {
      isOpen: false, // default is closed
      email: "",
      firstName: "",
      lastName: "",
      profilePicture: "",
      token: "",
    };
  },
  async mounted() { //เมื่อเปิดให้ทำเลย
    try {
      this.token = localStorage.getItem("token"); //เอาโทเค่นใน local ไปเก็บไว้ใน token
      if (!this.token) { //ถ้าไม่มีโทเคให้กลับไปหน้าล็อคอิน
        console.error("User token not found");
        router.push("/"); 
      }

      const email = localStorage.getItem("email"); //เอาเมลใน local ไปเก็บไว้ใน email

      //ดึงผู้ใช้ โดยดึงจากเมลที่ส่งไป
      const response = await axios.get(`http://localhost/socialweb/socialapp/src/server.php?email=${email}&action=getProfile`, {
        headers: { Authorization: `Bearer ${this.token}` },
      });

      if (response.data) { //ถ้ามีข้อมูลมาให้เก็บข้อมูลไปที่ตัวแปรนั้นๆ
        this.email = response.data.email;
        this.firstName = response.data.firstName;
        this.lastName = response.data.lastName;
        this.profilePicture = response.data.profilePicture;
      }
    } catch (error) {
      console.error("Error fetching profile:", error);
    }
  },
  methods: {
    toggleMenu() { //เปิดเมนูตอนหน้าจอเล็ก
      this.isOpen = !this.isOpen;
    }

    ,logout() { //ออกจากระบบ
      //ลบโทเค่นกับเมล ออกจาก local
      localStorage.removeItem("token"); 
      localStorage.removeItem("email");

      // เปลี่ยนเส้นทางไปหน้า Login
      this.$router.push("/"); 
    }

  }
};
</script>

<style scoped>
@import url('https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: absolute;
}

.container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

img {
  width: 4rem; /* ปรับขนาดรูปโปรไฟล์ */
  height: 4rem;
  border-radius: 9999px;
  border: 2px solid #6b46c1; /* border สีเดียวกับ purple */
}

h2 {
  font-size: 1.25rem; /* ปรับขนาดชื่อ */
  font-weight: bold;
}

p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.875rem;
}

.router-link {
  flex: 1;
  text-align: center;
}
</style>
