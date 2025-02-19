<template>
  <div class="max-w-md mx-auto bg-white p-3 rounded-lg shadow-md flex flex-col">
    <!-- ปุ่มสร้างโพสต์ -->
    <button 
      @click="togglePostForm" 
      class="bg-purple-500 text-white p-2 rounded-md hover:bg-purple-600 w-full">
      Create Post
    </button>

    <!-- ฟอร์มสร้างโพสต์-->
    <div v-if="isPostFormVisible" class="flex flex-col">
      <textarea 
        v-model="postContent" 
        class="w-full p-2 border border-gray-300 rounded-md mb-4"
        placeholder="What's on your mind?"
        rows="4"
      ></textarea>

      <!-- ปุ่มสำหรับแนบรูปภาพ -->
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Attach Image</label>
        <input 
          type="file" 
          accept="image/*" 
          @change="handleImageUpload" 
          class="block w-full text-sm text-gray-500 border rounded-md"
        />
        <div v-if="imagePreview" class="mt-2">
          <img :src="imagePreview" alt="Image Preview" class="w-full h-auto rounded-md"/>
        </div>
      </div>

      <!-- ปุ่มสำหรับแนบวิดีโอ -->
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Attach Video</label>
        <input 
          type="file" 
          accept="video/*" 
          @change="handleVideoUpload" 
          class="block w-full text-sm text-gray-500 border rounded-md"
        />
        <div v-if="videoPreview" class="mt-2">
          <video :src="videoPreview" controls class="w-full h-auto rounded-md"/>
        </div>
      </div>

      <!-- ปุ่มโพสต์ -->
      <button @click="createPost" class="bg-green-500 text-white p-2 rounded-md hover:bg-green-600 w-full">
        Post
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  name: 'CreatePost',
  data() {
    return {
      isPostFormVisible: false,
      postContent: '',
      imagePreview: null,
      videoPreview: null,
      likes: 0,
    };
  },
  methods: {
    togglePostForm() {
      this.isPostFormVisible = !this.isPostFormVisible;
    },

    async createPost() {
      //ถ้ามีไฟล์รูปกับไฟล์วิดีโอจะไม่สามารถอัพโหลดได้
      if (this.imagePreview && this.videoPreview) {
        alert("You can only upload image or a video.");
        return;
      }

      if (this.postContent.trim()) {
        const formData = new FormData(); //สร้างเพื่อเก็บข้อมูลรอส่งไปฐานข้อมูล
        formData.append('action', 'createPost');
        formData.append('content', this.postContent);
        formData.append('timestamp', new Date().toLocaleString());
        formData.append('likes', Number(this.likes));

        // ดึงอีเมลของผู้ใช้จากlocal
        const userEmail = localStorage.getItem("email");
        if (userEmail) { //ถ้ามีเก็บเข้า formdata
          formData.append('email', userEmail);
        } else {
          alert('User email not found!');
          return;
        }

        //แนบไฟล์
        if (this.imagePreview) { //แนบไฟล์รูป
          const imageBlob = this.dataURLtoBlob(this.imagePreview); //แปลงให้เป็นblob
          formData.append('image', imageBlob, 'image.jpg');
        }
        if (this.videoPreview) { //แนบไฟล์วิดีโอ
          const videoBlob = this.dataURLtoBlob(this.videoPreview); //แปลงให้เป็นblob
          formData.append('video', videoBlob, 'video.mp4');
        }

        try {
          const response = await axios.post("http://localhost/socialweb/socialapp/src/server.php", formData); //ส่งformdataไปฐานข้อมูล
          const result = response.data;
          if (response.status === 200) { //ถ้าส่งสำเร็จ
            console.log('Post created successfully:', result);
            this.resetForm();
          } else { 
            alert('Error creating post: ' + result.message);
          }
        } catch (error) {
          alert('There was an error creating your post.');
        }
      } else {
        alert('Please write something to post!');
      }
    },
    //แปลงเป็นblob
    dataURLtoBlob(dataURL) {
      const byteString = atob(dataURL.split(',')[1]);
      const mimeString = dataURL.split(',')[0].split(':')[1].split(';')[0];
      const ab = new ArrayBuffer(byteString.length);
      const ua = new Uint8Array(ab);
      for (let i = 0; i < byteString.length; i++) {
        ua[i] = byteString.charCodeAt(i);
      }
      return new Blob([ab], { type: mimeString });
    },
    //อัพโหลดและโชว์ตัวอย่างรูป
    handleImageUpload(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          this.imagePreview = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    },
    //อัพโหลดและโชว์ตัวอย่างวิดีโอ
    handleVideoUpload(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          this.videoPreview = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    },

    //รีเซ็ตฟอร์ม
    resetForm() {
      this.postContent = '';
      this.imagePreview = null;
      this.videoPreview = null;
      this.isPostFormVisible = false;
    }
  }
};
</script>

<style scoped>
  textarea {
    resize: none;
  }
  input[type="file"] {
    padding: 8px;
  }
  button {
    transition: background-color 0.3s ease;
  }
  div{
    position: relative;
  }
  button{
    margin-top: 2.5rem;
  }
</style>
