<template>
  <div class="min-h-screen flex flex-col items-center pt-10">
    <div class="w-full max-w-lg mx-auto bg-white p-5 rounded-lg shadow-md mb-4 flex flex-col">
      <!-- รายชื่อผู้ใช้ -->
      <div v-if="users.length" class="flex flex-col space-y-4">
        <div v-for="user in users" :key="user._id" class="flex justify-between items-center p-4 border-b">
          <div class="flex items-center">
            <!-- โชว์รูปโปรไฟล์ -->
            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-300">
              <img v-if="user.profilePicture" :src="'http://localhost/socialweb/socialapp/src/' + user.profilePicture" alt="Profile" class="w-full h-full object-cover">
              <div v-else class="w-full h-full bg-gray-300 flex items-center justify-center text-white font-bold">
                {{ user.email.charAt(0).toUpperCase() }}
              </div>
            </div>
            <!-- โชว์ชื่อเมล -->
            <div class="ml-2">
              <h3 class="font-bold text-sm text-purple-600 sm:text-base">{{ user.firstName }} {{ user.lastName }}</h3>
              <h3 class="font-bold text-xs sm:text-sm">{{ user.email }}</h3>
            </div>
          </div>
          <!-- ปุ่มส่งข้อความ -->
          <button @click="selectUser(user)" class="px-4 py-2 ml-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-xs sm:text-sm">
            Chat
          </button>
        </div>
      </div>
      <div v-else class="text-gray-500 text-center">Loading users...</div>
    </div>

    <!-- โชว์่ว่ากำลังแชทกับใคร -->
    <div v-if="selectedUser" class="mt-6 w-full max-w-lg mx-auto bg-gray-100 p-4 rounded-lg shadow-md">
      <div class="text-xl font-bold mb-4">
        Chat with <div class="text-xl font-bold text-purple-600">{{ selectedUser.firstName }} {{ selectedUser.lastName }}</div> 
        <div class="text-sm mb-4 text-gray-500">{{ selectedUser.email }}</div>
      </div>

      <!-- แสดงข้อความแชท -->
      <div class="chat-box mb-4">
        <div v-if="messages.length">
          <div v-for="message in messages" :key="message._id" class="message flex justify-between items-center">
            <!-- โชว์ข้อความ -->
            <div>
              <p><strong>{{ message.sender }}:</strong> {{ message.text }}</p>
              
              <div v-if="message.file && message.fileType" class="flex justify-center">
                <div v-if="message.fileType === 'image'">
                  <img :src="'http://localhost/socialweb/socialapp/src/'+message.file" class="w-32 h-32 object-cover mt-2 rounded" />
                </div>
                <div v-if="message.fileType === 'video'">
                  <video :src="'http://localhost/socialweb/socialapp/src/'+message.file" controls class="w-32 h-32 mt-2 rounded"></video>
                </div>
              </div>
            </div>
            <!-- ปุ่มลบข้อความ -->
            <button v-if="message.sender === currentUser " @click="deleteMessage(message._id)" class="ml-4 text-gray-500 rounded-lg hover:text-gray-700">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
        <div v-else class="text-gray-500 text-center">No messages yet.</div>
      </div>

      <!-- ฟอร์มส่งข้อความ -->
      <div class="flex flex-wrap gap-2">
        <!-- ช่องให้ส่งข้อความ -->
        <input v-model="newMessage" type="text" class="flex-grow p-2 border rounded text-sm sm:text-base" placeholder="Type a message..." />
        <!-- ช่องแแนบไฟล์ -->
        <input type="file" @change="handleFileUpload" class="hidden" ref="fileInput" />
        <!-- ปุ่มแนบไฟล์ -->
        <button @click="triggerFileUpload" class="bg-gray-500 text-white p-2 rounded text-sm sm:text-base"><i class="fa-solid fa-arrow-up"></i></button>
        <!-- ปุ่มส่งข้อความ -->
        <button @click="sendMessage" class="bg-blue-500 text-white p-2 rounded text-sm sm:text-base">Send</button>
      </div>
      <p v-if="selectedFileName" class="text-gray-600 text-xs mt-1">{{ selectedFileName }}</p>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "MessageBody",
  data() {
    return {
      users: [],
      selectedUser: null,
      messages: [],
      newMessage: "",
      selectedFile: null,
      selectedFileName: "",
      currentUser: localStorage.email,
    };
  },
  methods: {
    async loadUsers() { //โหลดผู้ใช้
      try {
        const response = await axios.get("http://localhost/socialweb/socialapp/src/server.php?action=loadUsers");
        this.users = response.data; //เอาไปไว้ในอาเรย์uesrs
      } catch (error) {
        console.error("Error loading users:", error);
      }
    },

    selectUser(user) { //เลือก user เพื่อไปโหลดข้อความ
      this.selectedUser = user;
      this.messages = [];
      this.loadMessages();
    },
    
    async loadMessages() { //โหลดข้อความ
      if (!this.selectedUser) return; //ถ้าไม่มีจะปล่อยว่าง
      try {
        const response = await axios.get("http://localhost/socialweb/socialapp/src/server.php", { //ดึงข้อมูลจากฐานข้อมูล
          params: {
            action: "loadMessages",
            user1: this.currentUser,
            user2: this.selectedUser.email,
          },
        });
        this.messages = response.data.map(message => ({ //โหลดมาเก็บในอาเรย์ messages
          _id: message._id || message.id,  
          sender: message.sender,
          receiver: message.receiver,
          text: message.text,
          file: message.file,
          fileType: message.fileType,
          timestamp: message.timestamp
        }));
      } catch (error) {
        console.error("Error loading messages:", error);
      }
    },


    async sendMessage() { //ส่งข้อความ
      if (!this.newMessage.trim() && !this.selectedFile) return; //ถ้าไม่มีข้อความและไฟล์ ปล่อยว่าง

      let formData = new FormData(); //สร้างไว้เก็บเพื่อรอส่งกลับฐานข้อมูล
      //เอาข้อมูลพวกนี้เก็บลง formdata
      formData.append("action", "sendMessage");
      formData.append("sender", this.currentUser);
      formData.append("receiver", this.selectedUser.email);
      formData.append("text", this.newMessage);
      if (this.selectedFile) { //ถ้าเลือกไฟล์ไว้ก็เอาเก็บด้วย
        formData.append("file", this.selectedFile);
      }

      try { //สงไปที่ฐานข้อมูล
        await axios.post("http://localhost/socialweb/socialapp/src/server.php", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        //ทำให้แต่ละช่องว่าง
        this.loadMessages();
        this.newMessage = "";
        this.selectedFile = null;
      } catch (error) {
        console.error("Error sending message:", error);
      }
    },

    handleFileUpload(event) { //โชว์ไฟล์ที่เลือก
      this.selectedFile = event.target.files[0];
      this.selectedFileName = this.selectedFile ? this.selectedFile.name : "";
    },

    triggerFileUpload() { //ปุ่มสำหรับอัพโหลดไฟล์
      this.$refs.fileInput.click();
    },

    async deleteMessage(messageId) { //ลบข้อความ
      if (!messageId) { //ถ้าไม่มี messageId ของข้อความนั้น
        console.error("Error: messageId is undefined");
        return;
      }

      try { //ส่งไอดีกับเมลคนลบไปฐานข้อมูลเพื่อลบ
        const response = await axios.post("http://localhost/socialweb/socialapp/src/server.php", {
          action: "deleteMessage",
          messageId: messageId,
          localEmail : localStorage.email
        });

        console.log("Delete response:", response.data);

        // โหลดแชทใหม่หลังจากลบ
        this.loadMessages();
      } catch (error) {
        console.error("Error deleting message:", error);
      }
    }


  },
  created() {
    this.loadUsers(); //โหลดผู้ใช้ไว้เลย
  },
};
</script>

<style scoped>
.chat-box {
  background-color: #f7f7f7;
  padding: 10px;
  border-radius: 5px;
  max-height: 300px;
  overflow-y: auto;
}

.message {
  margin-bottom: 10px;
}
</style>
