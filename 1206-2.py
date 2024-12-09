from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time
import mysql.connector
from bs4 import BeautifulSoup
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.chrome import ChromeDriverManager  # 自動管理 ChromeDriver

# 設置 Selenium 的 Chrome 驗證
chrome_options = Options()
# chrome_options.add_argument("--headless")  # 選擇性：在後台運行

chrome_service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=chrome_service, options=chrome_options)

# 連接到 MySQL
db_connection = mysql.connector.connect(
    host="127.0.0.1",
    user="root",
    password="",
    database="0819",
    charset='utf8mb4'
)

cursor = db_connection.cursor()

# 基礎URL
base_url = "https://www.redcross.org.tw/home.jsp?serno=201205070020"

# 打開初始頁面
driver.get(base_url)

# 等待頁面加載完成
WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.CLASS_NAME, 'box')))

# 開始爬取數據的函數
def scrape_current_page():
    try:
        # 爬取當前頁面內容
        page_source = driver.page_source
        soup = BeautifulSoup(page_source, 'html.parser')
        
        divs = soup.find_all('div', class_='box')
        if divs:
            for div in divs:
                # 提取日期
                date_tag = div.find('p', class_='date')
                date_text = date_tag.get_text(strip=True) if date_tag else "無日期"

                # 篩選日期為 2024 開頭的項目
                if not date_text.startswith("2024-"):
                    continue

                # 提取圖片 URL
                img_tag = div.find('div', class_='pic').find('img')
                img_url = img_tag.get('src') if img_tag else "無圖片"
                if not img_url.startswith("http"):
                    img_url = base_url + img_url

                # 提取標題
                title_tag = div.find('div', class_='info').find('p', class_='stitle')
                title = title_tag.get_text(" ", strip=True) if title_tag else "無標題"

                # 篩選標題包含「失智」
                if "失智" not in title:
                    continue

                # 提取連結
                link_tag = div.find('div', class_='more').find('a')
                link = link_tag.get('href') if link_tag else "無連結"
                if not link.startswith("http"):
                    link = base_url + link

                # 插入資料到資料庫
                try:
                    cursor.execute(
                        "INSERT INTO activity1 (activity_title, activity_link, activity_image) "
                        "VALUES (%s, %s, %s)",
                        (title, link, img_url)
                    )
                    db_connection.commit()
                    print(f"已插入資料：標題: {title}, 連結: {link}, 圖片: {img_url}")
                except mysql.connector.Error as err:
                    print(f"插入資料時出錯: {err}")
                    db_connection.rollback()
        else:
            print("當前頁面沒有數據可爬取")
    except Exception as e:
        print(f"發生異常: {e}")

# 點擊下一頁的函數
def go_to_next_page():
    try:
        # 等待「下一頁」按鈕存在
        next_button = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.CLASS_NAME, 'next'))
        )
        next_button.click()
        # 等待新頁面加載完成
        time.sleep(2)  # 等待網頁加載
        return True
    except TimeoutException:
        print("無法找到下一頁按鈕或已經到達最後一頁")
        return False
    except Exception as e:
        print(f"點擊下一頁失敗: {e}")
        return False

# 主爬取邏輯，最多爬取 12 頁
page_count = 0
while page_count < 12:
    print(f"正在爬取第 {page_count + 1} 頁數據...")
    scrape_current_page()
    if go_to_next_page():
        page_count += 1
    else:
        print("已經到達最後一頁或無法繼續爬取")
        break

# 關閉連接
cursor.close()
db_connection.close()
driver.quit()
print("爬取完成")
