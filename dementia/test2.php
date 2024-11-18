<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>測試</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
<span><i class="bi bi-toggle-on"></i></span>
    <script>


            // 定義查詢地址
let address = "新北市三重區新北大道一段7號";
const urlBase = "https://nominatim.openstreetmap.org/search";

// 首先分段查詢，只查縣市和區域
let baseUrl = `${urlBase}?format=json&q=${encodeURIComponent("新北市三重區")}`;

fetch(baseUrl)
  .then((response) => response.json())
  .then((data) => {
    if (data.length > 0) {
      // 取得縣市與區域的經緯度，設定為地圖中心
      let latitude = data[0].lat;
      let longitude = data[0].lon;

      console.log(`初步位置 - 經度: ${longitude}, 緯度: ${latitude}`);
      
      // 使用上一步的經緯度結果，進一步縮小範圍查詢街道
      let preciseUrl = `${urlBase}?format=json&street=${encodeURIComponent(
        "新北大道一段 7號"
      )}&city=${encodeURIComponent("三重區")}&state=${encodeURIComponent(
        "新北市"
      )}`;

      fetch(preciseUrl)
        .then((response) => response.json())
        .then((preciseData) => {
          if (preciseData.length > 0) {
            // 獲取更精確的經緯度
            latitude = preciseData[0].lat;
            longitude = preciseData[0].lon;
            console.log(`精確位置 - 經度: ${longitude}, 緯度: ${latitude}`);
          } else {
            console.log("無法取得更精確的經緯度");
          }
        })
        .catch((error) => console.error("精確查詢錯誤:", error));
    } else {
      console.log("初步查詢失敗");
    }
  })
  .catch((error) => console.error("地址查詢錯誤:", error));

    </script>
</body>

</html>