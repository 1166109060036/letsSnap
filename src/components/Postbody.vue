<template>
  <div class="min-h-screen flex items-start justify-center">
    <div class="max-w-md mx-auto bg-white p-5 rounded-lg shadow-md mb-4 flex flex-col">
      
      <!-- ปุ่มเลื่อนโพสต์ -->
      <div class="flex justify-between mb-3">
        <button @click="previousPost" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
          ← Newer Post
        </button>
        <button @click="nextPost" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
          Older Post →
        </button>
      </div>
      
      <!-- ฟอร์มแก้ไขโพสต์ -->
      <div v-if="editingPost" class="flex flex-col">
        <textarea 
          v-model="editContent" 
          class="w-full p-2 border border-gray-300 rounded-md mb-4"
          placeholder="Edit your post..."
          rows="4"
        ></textarea>

        <!-- ปุ่มบันทึก -->
        <button @click="saveEditPost(post._id)" class="bg-green-500 text-white p-2 rounded-md hover:bg-green-600 w-full">
          Save
        </button>

        <!-- ปุ่มยกเลิก -->
        <button @click="cancelEdit" class="bg-gray-400 text-white p-2 rounded-md hover:bg-gray-500 w-full mt-2">
          Cancel
        </button>
      </div>

      <!-- โชว์เนื้อหาโพสต์ -->
      <div v-if="post && post.email" class="flex flex-col items-center">
        <div class="flex items-center mb-2">
          <!-- โชว์โปรไฟล์คนโพสต์ -->
          <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold">
            <img v-if="post?.profilePicture" :src="post.profilePicture" class="w-full h-full rounded-full object-cover">
            <span v-else>{{ post?.email?.charAt(0)?.toUpperCase() || "?" }}</span>
          </div>
          <!-- โชว์เมลกับเวลาคนโพสต์ -->
          <div class="ml-2">
            <h3 class="font-bold text-purple-600">{{ post?.email || "Unknown User" }}</h3>
            <p class="text-gray-500 text-sm">{{ formatTimestamp(post?.timestamp) }}</p>
          </div>
          <!-- ปุ่มแก้ไขโพสต์กับลบโพสต์ -->
          <button v-if="post?.email === email" @click="startEditPost(post)" class="ml-4 text-gray-500 rounded-lg hover:text-yellow-600"><i class="fa-solid fa-pen-to-square"></i></button>
          <button v-if="post?.email === email" @click="deletePost(post._id)" class="ml-4 text-gray-500 rounded-lg hover:text-red-600"><i class="fa-solid fa-trash"></i></button>
        </div>
        <p class="text-lg font-semibold mb-2">{{ post?.content || "No content available" }}</p>

        <!-- โชว์รูปภาพหรือวิดีโอ -->
        <div v-if="post?.image" class="mb-2">
          <img :src="'http://localhost/socialweb/socialapp/src/' + post.image" alt="Post Image" class="rounded-lg">
        </div>
        <div v-if="post?.video" class="mb-2">
          <video controls class="rounded-lg">
            <source :src="'http://localhost/socialweb/socialapp/src/' + post.video" type="video/mp4">
            Your browser does not support the video tag.
          </video>
        </div>

        <!-- ปุ่มกดไลค์กับคอมเมนต์ -->
        <div class="flex justify-between items-center mt-3 text-gray-600">
          <button @click="likePost(post._id)" class="flex items-center space-x-1 hover:text-purple-500">
            <i class="fa-solid fa-thumbs-up"></i>            
            <span>{{ post?.likes || 0 }}</span>
          </button>
          <button @click="toggleCommentSection" class="hover:text-purple-500 ml-4"><i class="fa-solid fa-comment"></i> comment</button>
        </div>

        <!-- ฟอร์มคอมเมนต์ -->
        <div v-if="showCommentSection" class="mt-4 w-full">
          <!-- เพิ่มคอมเมนต์ -->
          <textarea v-model="newComment" placeholder="Write a comment..." class="w-full p-2 border rounded-lg" rows="1"></textarea>
          <!-- ปุมเพิ่มคอมเมนต์ -->
          <button @click="createComment" class="mt-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
            Submit Comment
          </button>

          <!-- โชว์คอมเมนต์ -->
          <div v-if="post?.comments && post.comments.length" class="mt-2 w-full">
            <h4 class="font-semibold">Comments:</h4>
            <div v-for="(comment, index) in post.comments" :key="index" class="mt-2 p-2 border-b">
              <div class="flex items-center mb-2">
                <!-- โปรไฟล์เจ้าของคอมเมนต์ -->
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold">
                  <img v-if="comment.profilePicture" :src="comment.profilePicture" class="w-full h-full rounded-full object-cover">
                  <span v-else>{{ comment.email.charAt(0).toUpperCase() }}</span>
                </div>
                <!-- เนื้อหาคอมเมนต์กับปุ่มลบคอมเมนต์ -->
                <div class="ml-2">
                  <p><strong>{{ comment.email }}  :</strong> {{ comment.text }}<button v-if="comment?.email === email" @click="deleteComment(comment.id)" class="ml-4 text-gray-500 rounded-lg hover:text-red-600"><i class="fa-solid fa-trash"></i></button>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div v-else class="text-gray-500 text-center">Loading...</div>

    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PostBodyComponent',
  data() {
    return {
      email: localStorage.getItem('email'),
      posts: [],
      currentPostIndex: 0,
      newComment: '', // ตัวแปรเก็บข้อความคอมเมนต์ใหม่
      showCommentSection: false, // ควบคุมการแสดงฟอร์มคอมเมนต์
      editingPost : false,
    };
  },
  computed: {
    post() {
      return this.posts.length > 0 ? this.posts[this.currentPostIndex] : null;
    }
  },
  methods: {
    async fetchPosts() {
      try { //ดึงโพสต์
          const response = await fetch("http://localhost/socialweb/socialapp/src/server.php?action=loadPosts");
          const data = await response.json(); //โหลดเป็นเจสัน

          // โหลดรูปโปรไฟล์โดยเอาเมลไปโหลด
          const postsWithProfile = await Promise.all(data.map(async (post) => {
              try {
                  const userRes = await fetch(`http://localhost/socialweb/socialapp/src/server.php?action=getProfile&email=${post.email}`);
                  const userData = await userRes.json(); //โหลดเป็นเจสัน
                  post.profilePicture = userData.profilePicture || null;
              } catch (error) {
                  console.error(`Error loading profile for ${post.email}:`, error);
                  post.profilePicture = null;
              }
              return post;
          }));

          this.posts = postsWithProfile.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp)); //เรียงเโพสต์ให้ใหม่มาก่อน
      } catch (error) {
          console.error('Error fetching posts:', error);
      }
    },
    async likePost(postId) { //กดไลค์โพสต์
      try {
        const post = this.posts.find(p => p._id === postId); //หาโพสต์ที่มีไอดีตรงกัน
        if (post) { //ถ้ามีโพสต์
          post.likes = (Number(post.likes) || 0) + 1; //บวกยอดไลค์
          await axios.post('http://localhost/socialweb/socialapp/src/server.php', {  //ส่งไปที่ฐานข้อมูล
            action: "likePost",
            postId, 
            likes: post.likes 
          });
        }
      } catch (error) {
        console.error('Error liking post:', error);
      }
    },

    async deletePost(postId) { //ลบโพสต์
      if (!postId) {//ถ้าโพสต์ไม่มีไอดี จะขึ้นerror
        alert("Error: Post ID is missing");
        return;
      }

      try {
        const response = await axios.post( //ส่งไปฐานข้อมูลเพื่อลบ
          "http://localhost/socialweb/socialapp/src/server.php", 
          { action: "deletePost", postId },
          { headers: { "Content-Type": "application/json" } } //ส่งแบบเจสัน
        );


        if (response.data.status === "success") { //ถ้าลบสำเร็จ
          alert("Post deleted successfully");
          this.posts = this.posts.filter(post => post._id !== postId); //อัพเดทอาเรย์โพสต์่ว่าลบแล้ว
        } else {
          alert("Fail to delete post: " + response.data.message);
        }
      } catch (error) {
        console.error("Error deleting post:", error);
      }
    },

    nextPost() { //กดเพื่อไปโพสต์ใหม่กว่า
      if (this.currentPostIndex < this.posts.length - 1) {
        this.currentPostIndex += 1;
      }
    },
    previousPost() { //กดเพื่อไปโพสต์เก่ากว่า
      if (this.currentPostIndex > 0) {
        this.currentPostIndex -= 1;
      }
    },

    formatTimestamp(timestamp) { //ระบุเวลา
      return timestamp ? new Date(timestamp).toLocaleString() : "Unknown date";
    },

    startEditPost(post) { //ปุ่มแก้ไขโพสต์
      this.editingPost = true;
      this.editContent = post.content;
    },
  cancelEdit() { //ปุ่มออกจากแก้ไขโพสต์
    this.editingPost = false;
    this.editContent = '';
  },
  handleEditImageUpload(event) { //โชว์ว่ากำลังเลือกรูปอะไร
    const file = event.target.files[0];
    if (file) {
      this.editImagePreview = URL.createObjectURL(file);
      this.editFile = file;
    }
  },

  handleEditVideoUpload(event) { //โชว์ว่ากำลังเลือกไฟล์อะไร
    const file = event.target.files[0];
    if (file) {
      this.editVideoPreview = URL.createObjectURL(file);
      this.editFile = file;
    }
  },

  async saveEditPost(postId) { //แก้ไขโพสต์
    try {
      let formData = new FormData(); //สร้างอารเรย์
      //เอาพวกนี้ใส่อาเรย์
      formData.append("action", "editPost");
      formData.append("postId", postId);
      formData.append("content", this.editContent);
      
      await axios.post('http://localhost/socialweb/socialapp/src/server.php', formData, { //ส่งไปที่ฐนาข้อมูล
        headers: { "Content-Type": "multipart/form-data" } //ส่้งไปแบบธรรมดา
      });

      //โหลดโพสต์ใหม่
      await this.fetchPosts();

      this.editingPost = false;
      this.editFile = null;
    } catch (error) {
      console.error('Error editing post:', error);
    }
  },


  async fetchComments(postId) { //โหลดคอมเมนต์
    try {
      const response = await axios.get(`http://localhost/socialweb/socialapp/src/server.php?action=getComments&postId=${postId}`); //ดึงคอมเมนต์ตามโพสต์นั้นๆ
      if (response.data.status === "success") { //ถ้าดึงได้
        const commentsWithProfiles = await Promise.all(response.data.comments.map(async (comment) => { //ดึงคอมเมนต์มา
          const profileResponse = await axios.get(`http://localhost/socialweb/socialapp/src/server.php?action=getProfile&email=${comment.email}`); //เอาเมลจากคอมเมนต์ไปโหลดรูปโปรไฟล์
          comment.profilePicture = profileResponse.data.profilePicture || null;
          return comment;
        }));
        const post = this.posts.find(p => p._id === postId); //หาโพสต์ที่ตรงกับโพสต์ของคอมเมนต์
        if (post) { //ถ้ามี
          post.comments = commentsWithProfiles; //เอาคอมเมนต์ที่ดึงมามใส่
        }
      }
    } catch (error) {
      console.error("Error fetching comments:", error);
    }
  },

  async createComment() { //สร้างคอมเมนต์
    if (!this.newComment.trim()) { //ถ้าส่งแบบเปล่าๆจะส่งไม่ได้
      alert("Comment cannot be empty!");
      return;
    }

    try { 
      let formData = new FormData(); //สร้างไว้รอเอาข้อมูลส่ง
      //เอาไปใส่ใน formData
      formData.append("action", "addComment");
      formData.append("postId", this.post._id); // ใช้ post._id
      formData.append("email", this.email);
      formData.append("comment", this.newComment);

      const response = await axios.post( //ส่งไปฐานข้อมูล
        "http://localhost/socialweb/socialapp/src/server.php",
        formData,
        { headers: { "Content-Type": "multipart/form-data" } }
      );

      if (response.data.status === "success") {
        this.newComment = ""; // 
        await this.fetchComments(this.post._id); //โหลดคอมเมนต์
      } else {
        alert("Failed to add comment: " + response.data.message);
      }
    } catch (error) {
      console.error("Error adding comment:", error);
    }
  },

  //ปุ่มโชว์คอมเมนต์
  toggleCommentSection() {
    this.showCommentSection = !this.showCommentSection;
    if (this.showCommentSection) {
      this.fetchComments(this.post._id); // โหลดคอมเมนต์
    }
  },
  //ลบคอมเมนต์
  async deleteComment(commentId) {
    if (!commentId) { //ถ้าไม่มีคอมเมนต์ไอดี
      alert("Error: Comment ID is missing");
      return;
    }
    try {
      const response = await axios.post( //ส่งไปฐานข้อมูล
        "http://localhost/socialweb/socialapp/src/server.php",
        { action: "deleteComment", commentId },
        { headers: { "Content-Type": "application/json" } } //ส่งแบบเจสัน
      );

      if (response.data.status === "success") { //ส่งสำเร็จก็จะลบคอมเมนต์
        alert("Comment deleted successfully");
        this.post.comments = this.post.comments.filter(comment => comment._id !== commentId); //เอาคอมเมนต์ที่ลบไปบอกอาเรย์
      } else {
        alert("Failed to delete comment: " + response.data.message);
      }
    } catch (error) {
      console.error("Error deleting comment:", error);
    }
  }

},
  mounted() {
    //โหลดโพสต์กับคอมเมนตต์ไว้แต่เริ่ม
    this.fetchPosts(); 
    this.fetchComments();
    
  },

};
</script>

<style scoped>
@import '@fortawesome/fontawesome-free/css/all.css';
div {
  transition: all 0.3s ease;
  position: relative;
  margin-top: 1rem;
}
</style>