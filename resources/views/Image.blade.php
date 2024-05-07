<!-- resources/views/upload.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 400px;
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input[type="file"] {
            margin-bottom: 20px;
        }
        button[type="submit"] {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        .image-preview {
            display: none;
            margin-top: 20px;
        }
        .image-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Image Upload</h2>
        <form id="imageForm" action="{{ route('uploadImage') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="file" name="image" id="imageInput">
            <button type="submit">Upload</button>
        </form>
        <div class="image-preview" id="imagePreview">
            <img id="previewImage" src="#" alt="Image Preview">
        </div>
    </div>

    <script>
        document.getElementById("imageInput").addEventListener("change", function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function() {
                    const preview = document.getElementById("previewImage");
                    preview.src = reader.result;
                    document.getElementById("imagePreview").style.display = "block";
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
