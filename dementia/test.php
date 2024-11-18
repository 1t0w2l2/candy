<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>經緯度查詢</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
    <div class="address-row">
        <div class="form-floating">
            <input type="text" class="form-control" id="lat" name="lat" required
                value="<?php echo isset($_POST['lat']) ? htmlspecialchars($_POST['lat']) : ''; ?>">
            <label for="lat">地圖顯示經度 (介於 119.5 至 122.0)</label>
        </div>
    </div>

    <div class="address-row">
        <div class="form-floating">
            <input type="text" class="form-control" id="lng" name="lng" required
                value="<?php echo isset($_POST['lng']) ? htmlspecialchars($_POST['lng']) : ''; ?>">
            <label for="lng">地圖顯示緯度 (介於 20.5 至 25.5)</label>
        </div>
    </div>
    <span><i class="bi bi-toggle-on"></i></span>
    <script>
        // 定義地址各部分
        const street = "成功路五段420巷 17之3號";
        const city = "內湖區";
        const county = "台北市";

        // 建立 Nominatim API 查詢 URL
        const url = `https://nominatim.openstreetmap.org/search?format=json&street=${encodeURIComponent(street)}&city=${encodeURIComponent(city)}&county=${encodeURIComponent(county)}&country=台灣`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const latitude = data[0].lat;
                    const longitude = data[0].lon;
                    console.log(`Latitude: ${latitude}, Longitude: ${longitude}`);
                } else {
                    console.log("找不到對應的經緯度");
                }
            })
            .catch(error => console.error("Error:", error));



            
    </script>
</body>

</html>