<?php
$conn = new mysqli("localhost", "root", "", "hospital_db");
$id = $_GET['id'];
$res = $conn->query("SELECT * FROM hospital_news WHERE id = $id");
$news = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $news['title'] ?> - NFTH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body { font-family: 'Segoe UI', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 50px auto; padding: 20px; }
        .btn-back { display: inline-block; margin-bottom: 20px; color: #2ecc71; text-decoration: none; font-weight: bold; }
        .detail-img { width: 100%; border-radius: 10px; margin-bottom: 30px; }
        .meta { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 10px; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        .content { font-size: 1.1rem; white-space: pre-line; }
        .back-btn { 
            display: inline-block; 
            color: #000000;          
            text-decoration: none;   
            font-weight: bold; 
            margin-bottom: 25px; 
            cursor: pointer; 
            transition: 0.2s; 
        }

        .back-btn:hover { 
            color: #333333;          
            transform: translateX(-3px); 
        }

    </style>
</head>
<body>
    <div class="container">
        <a onclick="window.close();" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to News
        </a>        
        <div class="meta"><?= date('F d, Y', strtotime($news['news_date'])) ?> | <span style="text-transform:capitalize"><?= $news['category'] ?></span></div>
        <h1><?= htmlspecialchars($news['title']) ?></h1>
        <img src="<?= $news['image_path'] ?>" class="detail-img">
        <div class="content"><?= nl2br(htmlspecialchars($news['full_details'])) ?></div>
    </div>
</body>
</html>