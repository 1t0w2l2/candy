from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from bs4 import BeautifulSoup
from selenium.common.exceptions import TimeoutException
from webdriver_manager.chrome import ChromeDriverManager
import time
import mysql.connector
from urllib.parse import urljoin  # 引入 urljoin 來安全拼接 URL


# 連接到 MySQL 資料庫，並設置字符集為 UTF-8
db_connection = mysql.connector.connect(
    host="127.0.0.1",
    user="root",
    password="",
    database="0819",
    charset='utf8mb4'
)

# 建立游標對象
cursor = db_connection.cursor()

# 設置 Selenium 的 Chrome 驗證
chrome_options = Options()
# chrome_options.add_argument("--headless")  # 如果不需要界面，可以取消註釋

chrome_service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=chrome_service, options=chrome_options)

# 基礎 URL
base_url = "https://www.redcross.org.tw/home.jsp?serno=201205070020"

driver.get(base_url)

# 等待頁面加載完成
WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.CLASS_NAME, 'box')))


def scrape_current_page():
    results = []
    try:
        # 獲取頁面源代碼
        page_source = driver.page_source
        soup = BeautifulSoup(page_source, 'html.parser')

        # 找到所有目標區塊
        divs = soup.find_all('div', class_='box')
        for div in divs:
            # 提取日期
            date_tag = div.find('p', class_='date')
            date_text = date_tag.get_text(strip=True) if date_tag else "無日期"

            # 篩選日期為 2024 開頭的項目
            if not date_text.startswith("2024-"):
                continue

            # 提取標題
            title_tag = div.find('div', class_='info').find('p', class_='stitle')
            title = title_tag.get_text(" ", strip=True) if title_tag else "無標題"

            # 提取連結
            link_tag = div.find('div', class_='more').find('a')
            link = link_tag.get('href') if link_tag else "無連結"
            if not link.startswith("http"):
                link = urljoin(base_url, link)  # 使用 urljoin 確保連結拼接正確

            # 提取圖片連結
            img_tag = div.find('img')
            image = img_tag['src'] if img_tag else "無圖片"
            image = urljoin(base_url, image)  # 使用 urljoin 進行正確拼接

            # 獲取 info 區域的文字
            info_text = div.find('div', class_='info').get_text(" ", strip=True) if div.find('div', class_='info') else "無內文"

            # 檢查關鍵字「失智」
            if "失智" in info_text:
                print(f"找到關鍵字於: {title}")

                results.append({
                    "title": title,
                    "link": link,
                    "image": image
                })

                # 插入資料到資料庫
                try:
                    sql = "INSERT INTO activity1 (activity_title, activity_link, activity_image) VALUES (%s, %s, %s)"
                    cursor.execute(sql, (title, link, image))
                    db_connection.commit()
                    print(f"成功插入資料: {title}")
                except Exception as db_error:
                    print(f"插入資料失敗: {db_error}")

    except Exception as e:
        print(f"發生異常: {e}")
    return results


def go_to_next_page():
    try:
        # 嘗試找到並點擊下一頁按鈕
        next_button = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.CLASS_NAME, 'next'))
        )
        next_button.click()
        time.sleep(2)  # 等待網頁加載
        return True
    except TimeoutException:
        print("無法找到下一頁按鈕或已經到達最後一頁")
        return False
    except Exception as e:
        print(f"點擊下一頁失敗: {e}")
        return False


# 主爬取邏輯
all_results = []
page_count = 1

while page_count <= 12:  # 限制最多爬取12頁
    print(f"正在爬取第 {page_count} 頁數據...")
    results = scrape_current_page()
    if results:
        all_results.extend(results)
    else:
        print("當前頁面無符合條件的數據")

    if not go_to_next_page():
        break

    page_count += 1

# 關閉瀏覽器和資料庫連接
driver.quit()
cursor.close()
db_connection.close()

# 輸出爬取結果
if all_results:
    print("\n爬取結果：")
    for item in all_results:
        print(f"標題: {item['title']}")
        print(f"連結: {item['link']}")
        print(f"圖片: {item['image']}\n")
else:
    print("未找到任何符合條件的數據")
