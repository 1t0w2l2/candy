from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time

# 設置 Chrome 選項
options = Options()
options.add_argument("--start-maximized")  # 啟動時最大化視窗
options.add_argument("--disable-notifications")  # 禁用通知
options.add_experimental_option("detach", True)  # 讓瀏覽器在腳本結束後保持打開

# 設置 ChromeDriver
service = Service(ChromeDriverManager().install())

# 啟動瀏覽器
driver = webdriver.Chrome(service=service, options=options)

try:
    # 訪問 Facebook 登入頁面
    driver.get("https://www.facebook.com/")
    print("正在訪問 Facebook 登入頁面...")

    # 等待登入表單元素出現
    WebDriverWait(driver, 15).until(EC.presence_of_element_located((By.ID, "email")))
    print("頁面已加載。")

    # 登入 Facebook
    username_field = driver.find_element(By.ID, "email")
    password_field = driver.find_element(By.ID, "pass")
    username_field.send_keys("0986684075")  # 替換為您的 Facebook 登入帳號
    password_field.send_keys("1qaz@WSX")  # 替換為您的 Facebook 密碼
    driver.find_element(By.NAME, "login").click()
    print("正在登入...")

    # 等待登入完成
    time.sleep(5)

    # 訪問目標 Facebook 頁面
    driver.get("https://www.facebook.com/peachgarden2017")  # 替換為目標頁面 URL
    print("正在訪問目標頁面...")

    # 滾動頁面以加載更多內容
    scroll_count = 3  # 滾動次數，可根據需要調整
    for _ in range(scroll_count):
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(3)  # 等待頁面加載

    # 查找指定類名的元素
    elements = driver.find_elements(By.CSS_SELECTOR, ".x78zum5.xdt5ytf.xz62fqu.x16ldp7u")
    print(f"找到 {len(elements)} 個元素，開始提取內容...\n")

    # 提取所有找到的元素內容
    for index, el in enumerate(elements):
        text_content = el.text.strip()  # 提取純文本
        class_attribute = el.get_attribute("class")  # 獲取 class 屬性

        # 排除特定 class 的元素
        excluded_classes = [
            "html-div xdj266r x11i5rnm xat24cr x1mh8g0r xexx8yu x4uap5 x18d9i69 xkhd6sd x78zum5 x1n2onr6 xh8yej3",
            "x9f619 x1ja2u2z x78zum5 x2lah0s x1n2onr6 x1nhvcw1 x1qjc9v5 xozqiw3 x1q0g3np xyamay9 xykv574 xbmpl8g x4cne27 xifccgj",
            "x1yztbdb",
            "x7wzq59",
            "x1n2onr6 x1ja2u2z x1jx94hy x1qpq9i9 xdney7k xu5ydu1 xt3gfkd x9f619 xh8yej3 x6ikm8r x10wlt62 xquyuld",
            "html-span xdj266r x11i5rnm xat24cr x1mh8g0r xexx8yu x4uap5 x18d9i69 xkhd6sd x1hl2dhg x16tdsg8 x1vvkbs"
        ]
        if any(excluded_class in class_attribute for excluded_class in excluded_classes):
            continue  # 跳過該元素

        # 排除「簡介」內容
        if "簡介" in text_content:
            continue

        # 排除含有「精選」的文章
        if "精選" in text_content:
            continue

        # 過濾不需要的文字（例如 讚、留言、傳送、分享）
        unwanted_keywords = ["讚", "留言", "傳送", "分享"]
        if any(keyword in text_content for keyword in unwanted_keywords):
            continue  # 如果包含不需要的關鍵字，跳過此元素

        # 過濾特定不需要的元素內容
        unwanted_texts = [
            "桃園市失智共同照護中心-桃園長庚",
            "粉絲專頁 · 非營利組織",
            "03 319 6200",
            "ad.taoyuan2017@gmail.com",
            "100% 推薦（6 則評論）",
            "2023年10月7日"
        ]
        if any(unwanted_text in text_content for unwanted_text in unwanted_texts):
            continue

        print(f"元素 {index + 1}:\n{text_content}\n")

    print("\n提取完成！")

except Exception as e:
    print(f"發生錯誤：{e}")

finally:
    print("腳本結束，瀏覽器保持打開狀態。")
    input("按 Enter 鍵結束腳本...")
