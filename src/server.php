<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require 'vendor/autoload.php'; 

use MongoDB\Client;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;


$uri = 'mongodb+srv://partouton:lN0b42aFElSX3zwZ@cluster0.0wje6.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0';
$client = new Client($uri);
$db = $client->mydatabase;
$collection = $db->users; 
$messagesCollection = $db->messages;


$secretKey = 'cA7tB@!z9sR12T#9p1XxA0v8q9JzT&F4j7W';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$pin = $data['pin'] ?? '';
$action = $data['action'] ?? '';


//login กับ register
if ($action === 'register') {
    // เช็คว่าเมลกับพาสได้ใส่มั้ย
    if (!empty($email) && !empty($password)) {
        $existingUser = $collection->findOne(['email' => $email]);
        //เช็คว่ามีเมลในฐานข้อมูลมั้ย
        if ($existingUser) {
            echo json_encode(["message" => "Email already exists"]);
            exit;
        }  
        //เข้ารหัสพาสกับพิน
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $hashedPin = password_hash($pin, PASSWORD_BCRYPT);
        //ส่งข้อมูล
        $insertResult = $collection->insertOne([
            'email' => $email,
            'password' => $hashedPassword,
            'pin' => $hashedPin,
            'firstName' => '',
            'lastName' => '',
            'profilePicture' => ''
        ]);
        //รับผลรับ
        if ($insertResult->getInsertedCount() > 0) {
            echo json_encode(["message" => "User registered successfully"]); 
        } else {
            echo json_encode(["message" => "Failed to register user"]);
        }
    } else {
        echo json_encode(["message" => "Missing required fields for registration"]);
    }
} elseif ($action === 'login') {
    // เช็คว่าเมลกับพาสได้ใส่มั้ย
    if (!empty($email) && !empty($password)) {

        $user = $collection->findOne(['email' => $email]);
        if ($user) {
            $maxAttempts = 3;
            $waitTime = 5 * 60; //รอ5นาที
            $currentTime = time();

            //จำกะดการเข้าสู่ระบบผิด
            if (isset($user['loginAttempts']) && $user['loginAttempts'] >= $maxAttempts) {
                $timePassed = $currentTime - $user['lastAttemptTime']; //เอาเวลาที่เข้าครั้งล่าสุดไปลบกับเวลาปัจจุบัน
                if ($timePassed < $waitTime) { 
                    $remainingTime = ceil(($waitTime - $timePassed) / 60); // เวลาที่เหลือในการรอ
                    echo json_encode(["message" => "Too many attempts. Please try again after $remainingTime minutes."]);
                    exit;
                } else { 
                    //รีเซ็ตการล็อกอินหลังจากเวลาครบ
                    $collection->updateOne(
                        ['email' => $email],
                        ['$set' => ['loginAttempts' => 0, 'lastAttemptTime' => 0]]
                    );
                }
            }

            //เช็ครหัส
            if (password_verify($password, $user['password'])) {
                // สร้างโทเคน jwt
                $issuedAt = time();
                $expirationTime = $issuedAt + 3600;  //จำกัดเวลาโทเคน1ชม
                $payload = [
                    'email' => $email,
                    'exp' => $expirationTime
                ];

                $jwt = JWT::encode($payload, $secretKey, 'HS256'); //ถอดรหัสโทเคน

                // รีเซ็ตตัวนับล็อกอินผิดถ้าล็อกอินได้
                $collection->updateOne(
                    ['email' => $email],
                    ['$set' => ['loginAttempts' => 0, 'lastAttemptTime' => 0]]
                );

                echo json_encode(["message" => "Login successful", "token" => $jwt]);
            } else { 
                // เพิ่มจำนวนการพยายามล็อกอินถ้าล็อคอินผืด
                $collection->updateOne(
                    ['email' => $email],
                    ['$inc' => ['loginAttempts' => 1], '$set' => ['lastAttemptTime' => $currentTime]]
                );
                echo json_encode(value:["message" => "Invalid email or password"]);
            }
        } else {
            echo json_encode(value: ["message" => "Invalid email or password"]);
        }
    } else {
        echo json_encode(value: ["message" => "required email and password"]);
    }
}


//แก้ไขโปรไฟล์ผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'saveProfile') {
    $email = $_POST['email'] ?? '';
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $profilePicture = $_FILES['profilePicture'] ?? null;

    if (!empty($email) && !empty($firstName) && !empty($lastName) && isset($profilePicture)) {
        // หาเมลในฐานข้อมูล
        $existingUser = $collection->findOne(['email' => $email]);
        //ุถ้าไม่เจอบอกว่าไม่เจอ
        if (!$existingUser) {
            echo json_encode(["message" => "User not found"]);
            exit;
        }

        //อัปโหลดรูปโปรไฟล์
        $uploadDir = 'src/uploads/'; //ตั้งที่อัพโหลดไฟล์
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true); // สร้างโฟลเดอร์ถ้ายังไม่มี
        }
        //อัพโหลดลงที่นี่
        $uploadFile = $uploadDir . basename($_FILES['profilePicture']['name']);
        
        if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $uploadFile)) { //ถ้าอัพโหลดไฟล์ได้เข้าเงื่อนไขนี้
            // อัปเดตข้อมูลโปรไฟล์ในฐานข้อมูล
            $updateData = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'profilePicture' => $uploadFile //อัพโหลดที่อยู่ของรูปไปในฐานข้อมูล
            ];

            // อัปเดตข้อมูลใน MongoDB
            $updateResult = $collection->updateOne(
                ['email' => $email], 
                ['$set' => $updateData]
            );

            if ($updateResult->getModifiedCount() > 0) {
                echo json_encode(["message" => "Profile updated successfully"]);
            } else {
                echo json_encode(["message" => "Failed to update profile"]);
            }
        } else {
            echo json_encode(["message" => "Failed to upload profile picture", "error" => $_FILES['profilePicture']['error']]);
        }
    } else {
        echo json_encode(["message" => "Invalid data, please check your input"]);
    }
    exit;
}
//แสดงโปรไฟล์ผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email']) && isset($_GET['action']) && $_GET['action'] === 'getProfile') {
    $email = $_GET['email'];

    if ($_GET['action'] === 'getProfile') {
        $collection = $db->users;
        //เช็คเมลในฐานข้อมูล
        $existingUser = $collection->findOne(['email' => $email]);
        if ($existingUser) {//ถ้ามีเมลนั้น
            //ส่งข้อมูลผู้ใช้กลับไป
            $baseUrl = 'http://localhost/socialweb/socialapp/src/';
            $profilePicturePath = $existingUser['profilePicture'] ?? '';

            $profilePictureUrl = $profilePicturePath ? $baseUrl . $profilePicturePath : '';
            //ส่งข้อมูลกลับไปเป็นเจสัน
            echo json_encode([
                'email' => $existingUser['email'],
                'firstName' => $existingUser['firstName'],
                'lastName' => $existingUser['lastName'],
                'profilePicture' => $profilePictureUrl // ใช้ URL ที่ถูกต้อง
            ]);
        } else {
            echo json_encode(["message" => "User not found"]);
        }
    } else {
        echo json_encode(["message" => "Invalid action"]);
    }
    exit;
}


//ส่วนโพสต์//
//สร้างโพสต์
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) == 'createPost') {
    if (isset($_POST['action']) && $_POST['action'] == 'createPost') {
        $content = $_POST['content']; //เนื้อหาโพสต์
        $timestamp = $_POST['timestamp']; //เวลาที่โพสต์
        $email = $_POST['email'];  // เมลคนโพสต์
        $likes = isset($_POST['likes']) ? (int)$_POST['likes'] : 0; // เปลี่ยนเป็น int ก่อนบันทึก

        // แนบไฟล์ (รูปภาพหรือวิดีโอ) หากมี
        if (isset($_FILES['image'])) { //ถ้าเป็นรูปเข้าเงื่อนไขนี้
            // เก็บไฟล์ภาพ
            $imagePath = uploadFile($_FILES['image']);
        }
        if (isset($_FILES['video'])) { //ถ้าเป็นวิดีโอเก็บนี้
            // เก็บไฟล์วิดีโอ
            $videoPath = uploadFile($_FILES['video']);
        }

        $postsCollection = $db->posts; 
        // เอาโพสต์ลงฐานข้อมูล
        $insertResult = $postsCollection->insertOne([
            'content' => $content,
            'email' => $email,
            'image' => isset($imagePath) ? $imagePath : null,
            'video' => isset($videoPath) ? $videoPath : null,
            'timestamp' => $timestamp = new MongoDB\BSON\UTCDateTime(),
            'likes' => $likes
        ]);

        if ($insertResult->getInsertedCount() > 0) {
            echo json_encode(['message' => 'Post created successfully']);
        } else {
            echo json_encode(['message' => 'Failed to create post']);
        }
    }
}
// ดึงโพสต์
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'loadPosts') {
    $postCollection = $db->posts;
    $userCollection = $db->users;
    //ดึงโพสต์โดยหาจากเวลาที่ใหม่ที่สุด
    $posts = $postCollection->find([], ['sort' => ['timestamp' => -1]]);
    $postArray = []; //สร้างอาเรย์
    //ทำลูปดึงโพสต์
    foreach ($posts as $post) {
        $post['_id'] = (string)$post['_id']; // แปลงObjectIdเป็นstring

        // เช็คtimestampว่ามีมั้ย
        if (isset($post['timestamp']) && $post['timestamp'] instanceof MongoDB\BSON\UTCDateTime) { //ถ้ามีเข้าเงื่อนไขนี้
            $dateTime = $post['timestamp']->toDateTime(); //ทำเวลาเป็นวันกับเวลา
            $dateTime->setTimezone(new DateTimeZone("Asia/Bangkok"));//แปลงเป็นเวลาไทย
            $post['timestamp'] = $dateTime->format('Y-m-d H:i'); //ทำเวลาให้อยู่ในรูป24ชม
        } else {
            $post['timestamp'] = 'ไม่มีข้อมูลเวลา';
        }

        //ดึงรูปจากผู้ใช้โดยใช้เมลคนโพสต์
        if (isset($post['email'])) { //ถ้ามีเข้าเงื่อนไขนี้
            $user = $userCollection->findOne(['email' => $post['email']], ['projection' => ['profilePicture' => 1]]); //หารูปจากเมล
            $user['profilePicture'] = $user['profilePicture'] ?? ''; 
        } else {
            $post['profilePicture'] = ''; // ถ้าไม่มีก็ปล่อยว่าง
        }
        
        $postArray[] = $post; //เอาที่ได้ไปใส่ในอาเรย์
    }

    echo json_encode($postArray);
    exit;
}
// กดไลค์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['postId']) && isset($data['action']) && $data['action'] === 'likePost') {
    $data = json_decode(file_get_contents("php://input"), true);

    
        $postCollection = $db->posts;
        $postId = $data['postId']; // ดึง postId จากเจสันที่ส่งมาจาก frontend

        // เพิ่มไลค์1
        $result = $postCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($postId)],
            ['$inc' => ['likes' => 1]] 
        );

        if ($result->getModifiedCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Like added']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add like']);
        }
        exit;
    
}
//ลบโพสต์
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($data["action"]) && $data["action"] === "deletePost") {
    global $db;
    
    if (!isset($data["postId"])) {
        echo json_encode(["status" => "error", "message" => "Post ID is required"]);
        die();
    }

    $postId = $data["postId"];
    $collection = $db->posts;
    $result = $collection->deleteOne(["_id" => new MongoDB\BSON\ObjectId($postId)]);
    
    if ($result->getDeletedCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Post deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Post not found or delete failed"]);
    }
}
//แก้ไขโพสต์
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "editPost") {
    $postId = $_POST["postId"];
    $content = $_POST["content"];

    try {
        $postsCollection = $db->posts; // เลือก Collection `posts`

        // ค้นหาโพสต์เดิม
        $post = $postsCollection->findOne(["_id" => new ObjectId($postId)]);

        if (!$post) {
            throw new Exception("Post not found.");
        }
        // อัปเดตโพสต์
        $updateData = ["content" => $content, "file_path" => $filePath];
        $result = $postsCollection->updateOne(["_id" => new ObjectId($postId)], ['$set' => $updateData]);

        if ($result->getModifiedCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Post updated successfully"]);
        } else {
            throw new Exception("Failed to update post.");
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}


// ฟังก์ชันอัปโหลดไฟล์สำหรับโพสต์และแชท
function uploadFile($file) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true); // สร้างโฟลเดอร์ถ้ายังไม่มี
    }

    // เปลี่ยนชื่อไฟล์ไม่ให้มันซ้ำกัน
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);  // นำเอานามสกุลไฟล์เดิม
    $newFileName = uniqid('img_', true) . '.' . $fileExtension;  // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำ
    $filePath = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return $filePath;  // ส่งคืนค่าตำแหน่งไฟล์
    }

    return null;  // ปล่อยว่างถ้าอัพไม่ได้
}
// ดึงข้มอูลผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'loadUsers') {
    // ดึงข้อมูลผู้ใช้ทั้งหมดจากusers
    $collection = $db->users;
    $users = $collection->find([]);

    
    $userArray = []; 
    foreach ($users as $user) { //ลูปเก็บข้อมูลให้ครบ
        //เก็บข้อมูลเป็นอาเรย์
        $userArray[] = [
            'email' => $user['email'],
            'firstName' => $user['firstName'] ?? '',
            'lastName' => $user['lastName'] ?? '',
            'profilePicture' => $user['profilePicture'] ?? ''
        ];
    }

    // ส่งข้อมูลผู้ใช้กลับไปลงอาเรย์
    echo json_encode($userArray);
    exit;
}


//ส่วนแชท//
$usersCollection = $db->users; // คอลเลกชัน Users
$messagesCollection = $db->messages; // คอลเลกชัน Messages
//ดึงแชท
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'loadUsers') {
        // ดึงรายชื่อผู้ใช้ทั้งหมด
        $users = $usersCollection->find([], ['projection' => ['_id' => 0]]);
        echo json_encode(iterator_to_array($users));
        exit;
    } elseif ($action === 'loadMessages') { //โหลดข้อความ
        // โหลดแชทระหว่าง 2 ผู้ใช้
        $user1 = $_GET['user1'] ?? '';
        $user2 = $_GET['user2'] ?? '';

        if (empty($user1) || empty($user2)) { //ถ้าไม่มี user1 และ 2
            echo json_encode(['error' => 'no user1 and user2']);
            exit;
        }

        // ค้นหาข้อความระหว่างผู้ใช้ทั้งสองคน
        $messages = $messagesCollection->find( //หาจากตารางข้อความ
            [
                '$or' => [ //หาอย่างใดอย่างนึง
                    ['sender' => $user1, 'receiver' => $user2],
                    ['sender' => $user2, 'receiver' => $user1]
                ]
            ],
            [
                'sort' => ['timestamp' => 1], //เรียงข้อความให้เก่าอยู๋บนสุด
                //ส่งเนื้อหาทั้งหมด
                'projection' => ['_id' => 1, 'sender' => 1, 'receiver' => 1, 'text' => 1, 'file' => 1, 'fileType' => 1, 'timestamp' => 1] 
            ]
        );

        $messagesArray = iterator_to_array($messages); //ทำให้เป็นอาเรย์
        if (empty($messagesArray)) {
            echo json_encode([]);
            exit;
        }

        // แปลง id เป็น string 
        foreach ($messagesArray as &$msg) {
            $msg['_id'] = (string) $msg['_id'];
        }

        echo json_encode($messagesArray);
        exit;
    }
}
//เพิ่มแชท
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sendMessage') {
    // รับค่าข้อมูลที่ส่งมา
    $sender = $_POST['sender'];
    $receiver = $_POST['receiver'];
    $text = $_POST['text'];
    $filePath = "";
    $fileType = ""; 

    // จัดการอัปโหลดไฟล์ 
    if (!empty($_FILES['file'])) {
        $targetDir = "uploads/"; //เอาไปใส่ในโฟลเดอร์เดียวกับโพสต์
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $filePath = $targetDir . basename($_FILES["file"]["name"]);

        // ตรวจสอบประเภทไฟล์
        $fileMimeType = mime_content_type($_FILES["file"]["tmp_name"]);
        if (strpos($fileMimeType, 'image/') === 0) {
            $fileType = 'image';
        } elseif (strpos($fileMimeType, 'video/') === 0) {
            $fileType = 'video';
        }

        // อัปโหลดไฟล์
        move_uploaded_file($_FILES["file"]["tmp_name"], $filePath);
    }

    // บันทึกข้อความลงฐานข้อมูล
    $messagesCollection->insertOne([
        'sender' => $sender,
        'receiver' => $receiver,
        'text' => $text,
        'file' => $filePath,
        'fileType' => $fileType, //เพิ่มประเภทไฟล์ลงในฐานข้อมูล
        'timestamp' => new MongoDB\BSON\UTCDateTime()
    ]);

    echo json_encode(['status' => 'success']); //ส่งสถานะสำเร็จ
    exit;
}
//ลบข้อความ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'deleteMessage') {
    //รับข้อมูลเจสันที่ส่งมา
    $json = file_get_contents("php://input");
    $data = json_decode($json, true); 

    
    if (!isset($data["action"]) || $data["action"] !== "deleteMessage") {
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        exit;
    }

    // ตรวจสอบว่า messageId ถูกส่งมามั้ย
    if (!isset($data["messageId"]) || empty($data["messageId"])) {
        echo json_encode(["status" => "error", "message" => "Message ID is required"]);
        exit;
    }

    try {
        // สร้าง ObjectId จาก messageId
        $messageId = new MongoDB\BSON\ObjectId($data["messageId"]); //ทำให้เป็น ObjectId

        // ลบข้อความที่ตรงกับ id
        $deleteResult = $messagesCollection->deleteOne(["_id" => $messageId]);

        // ตรวจสอบผลลัพธ์ของการลบ
        if ($deleteResult->getDeletedCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Message deleted"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Message not found"]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
    }
}


//รีเซ็ตรหัสผ่าน   
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'resetPassword') {
    // รับข้อมูลเจสันที่ส่งมา
    $data = json_decode(file_get_contents('php://input'), true);

    // ตรวจสอบว่า action, email, pin, และ newpassword ถูกส่งมาหรือไม่
    if (isset($data['email']) && isset($data['pin']) && isset($data['newpassword'])) {
        $email = $data['email'];
        $pin = $data['pin'];
        $newpassword = $data['newpassword'];

        // ใช้เมลที่รับมาค้นหาผู้ใช้ในฐานข้อมูล
        $existingUser = $collection->findOne(['email' => $email]);

        if ($existingUser) { //ถ้ามีเมลเข้าเงื่อนไขนี้
            // ตรวจสอบว่า PIN ที่กรอกตรงกับ PIN ที่เก็บไว้ในฐานข้อมูล
            if (password_verify($pin, $existingUser['pin'])) {
                // อัปเดต password ใหม่
                $updateResult = $collection->updateOne(
                    ['email' => $email], // ค้นหาผู้ใช้ที่มี email ตรง
                    ['$set' => ['password' => password_hash($newpassword, PASSWORD_BCRYPT)]] // อัปเดต password โดยการเข้ารหัส
                );

                if ($updateResult->getModifiedCount() > 0) {
                    echo json_encode(["message" => "Password updated successfully"]);
                } else {
                    echo json_encode(["message" => "Failed to update password"]);
                }
            } else {
                echo json_encode(["message" => "Invalid PIN"]);
            }
        } else {
            echo json_encode(["message" => "User not found"]);
        }
    } else {
        echo json_encode(["message" => "Invalid request data"]);
    }
}


//ส่วนคอมเมนต์//
$commentsCollection = $db->comments;
$postsCollection = $db->posts;
// เพิ่มคอมเมนต์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["action"]) && $_POST["action"] === "addComment") {
    $action = $_POST['action'] ?? '';

    if ($action === 'addComment') {
        $postId = $_POST['postId'] ?? '';
        $email = $_POST['email'] ?? '';
        $commentText = $_POST['comment'] ?? '';

        if (!$postId || !$email || !$commentText) { //ถ้าไม่มีทั้งหมดนี้
            echo json_encode(["status" => "error", "message" => "Missing required fields"]);
            exit;
        }
        //เอาคอมเมนต์ที่รับมาไปใส่อาเรย์นี้
        $newComment = [
            "postId" => new MongoDB\BSON\ObjectId($postId), //ทำเป็น ObjectId
            "email" => $email,
            "text" => $commentText,
            "timestamp" => new MongoDB\BSON\UTCDateTime() //ทำเป็นเวลา
        ];
        //เพิ่มลงฐานข้อมูล
        $insertResult = $commentsCollection->insertOne($newComment);

        if ($insertResult->getInsertedCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Comment added"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to add comment"]);
        }
        exit;
    }
}
//แสดงคอมเมนต์
if (isset($_GET['action']) && $_GET['action'] === 'getComments') {
    if (!isset($_GET['postId'])) {
        echo json_encode(["status" => "error", "message" => "postId is required"]);
        exit();
    }

    // ทำ postId เป็น ObjectId
    $postId = new MongoDB\BSON\ObjectId($_GET['postId']);

    try {
        // ดึงคอมเมนต์ที่ตรงกับ postId
        $comments = $commentsCollection->find(['postId' => $postId], [
            'sort' => ['timestamp' => -1] // เรียงจากใหม่ไปเก่า
        ]);

        $commentList = [];
        foreach ($comments as $comment) { //ทำลูปเพื่อดึงเก็บลงอาเรย์
            $commentList[] = [
                'id' => (string)$comment['_id'],
                'postId' => (string)$comment['postId'],
                'email' => $comment['email'],
                'text' => $comment['text'],
                'timestamp' => $comment['timestamp']
            ];
        }

        echo json_encode(["status" => "success", "comments" => $commentList]); //ส่งคอมเมนต์ที่เก็บเมื่อกี้ไป
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit();
}
//ลบคอมเมนต์
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($data["action"]) && $data["action"] === "deleteComment") {

    if (!isset($data["commentId"])) {
        echo json_encode(["status" => "error", "message" => "Comment ID is required"]);
        die();
    }
    
    $commentId = $data["commentId"]; //เอาไอดีที่รับมามาใส่
    $result = $commentsCollection->deleteOne(["_id" => new MongoDB\BSON\ObjectId($commentId)]); //ลบคอมเมนต์

    if ($result->getDeletedCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Comment deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Comment not found or delete failed"]);
    }
}








/*
try {
    $client->selectDatabase('admin')->command(['ping' => 1]);
    echo "Pinged your deployment. You successfully connected to MongoDB!\n";
} catch (Exception $e) {
    printf("Error: %s\n", $e->getMessage());
}
    */

?>




