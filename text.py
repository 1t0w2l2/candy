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

    # 判斷是否已經點擊過「查看更多」按鈕的標誌
    see_more_clicked = False

    while True:
        try:
            # 查找並點擊「查看更多」按鈕，只點擊一次
            if not see_more_clicked:
                see_more_buttons = driver.find_elements(By.XPATH, "//div[contains(@class, 'x1i10hfl') and contains(text(), '查看更多')]")
                if see_more_buttons:
                    # 等待“查看更多”按鈕變為可點擊
                    WebDriverWait(driver, 10).until(EC.element_to_be_clickable(see_more_buttons[0]))
                    
                    # 滾動到「查看更多」按鈕位置並點擊
                    driver.execute_script("arguments[0].scrollIntoView();", see_more_buttons[0])
                    time.sleep(1)  # 等待滾動完成
                    driver.execute_script("arguments[0].click();", see_more_buttons[0])  # 點擊「查看更多」
                    print("已點擊「查看更多」按鈕，正在加載完整貼文...")
                    see_more_clicked = True  # 設置標誌，表示已經點擊過「查看更多」按鈕
                    time.sleep(3)  # 等待頁面加載

                    # 等待文章內容完全加載後，開始抓取並打印內容
                    time.sleep(3)  # 等待更多內容加載
                    elements = driver.find_elements(By.XPATH, "//*[@class='html-div xdj266r x11i5rnm xat24cr x1mh8g0r xexx8yu x4uap5 x18d9i69 xkhd6sd']")

                    # 如果找到符合條件的文章，打印文章內容
                    if elements:
                        for element in elements:
                            try:
                                # 查找該 <div> 內的所有 <span> 元素
                                span_elements = element.find_elements(By.XPATH, ".//span")

                                # 提取並處理所有 <span> 的文本內容
                                if span_elements:
                                    for span in span_elements:
                                        text_content = span.text

                                        # 過濾不需要的文字（例如 讚、留言、傳送、分享）
                                        unwanted_keywords = ["讚", "留言", "傳送", "分享"]
                                        if any(keyword in text_content for keyword in unwanted_keywords):
                                            continue  # 如果包含不需要的關鍵字，跳過此元素
                                        
                                        # 輸出文章內容
                                        if text_content:
                                            print("完整文章內容:\n", text_content)  # 輸出完整內容

                            except Exception as e:
                                print(f"處理元素時發生錯誤: {e}")
                else:
                    print("沒有更多的「查看更多」按鈕，停止處理。")
                    break  # 如果沒有更多的「查看更多」按鈕，停止處理

        except Exception as e:
            print(f"查找文章內容發生錯誤: {e}")
            break

finally:
    driver.quit()
