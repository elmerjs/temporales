import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import NoAlertPresentException

# Configuración inicial
driver_path = 'C:/scripts/chromedriver-win64/chromedriver.exe'
proxy_address = "http://proxy.unicauca.edu.co:3128"

# Configurar proxy en variables de entorno
os.environ.update({
    "HTTP_PROXY": proxy_address,
    "HTTPS_PROXY": proxy_address,
    "NO_PROXY": "localhost,127.0.0.1"
})

# Configuración avanzada de Chrome para evitar alertas
chrome_options = Options()
chrome_options.add_argument("--start-maximized")
chrome_options.add_argument(f"--proxy-server={proxy_address}")
chrome_options.add_argument('--proxy-bypass-list=localhost;127.0.0.1')
chrome_options.add_argument("--disable-blink-features=AutomationControlled")
chrome_options.add_argument("--disable-infobars")
chrome_options.add_argument("--disable-notifications")
chrome_options.add_argument("--disable-popup-blocking")
chrome_options.add_argument("--disable-features=PasswordLeakDetection")

# Preferencias clave para evitar problemas con contraseñas
chrome_options.add_experimental_option("prefs", {
    "credentials_enable_service": False,
    "profile.password_manager_enabled": False,
    "profile.default_content_setting_values.notifications": 2,
    "safebrowsing.enabled": False,
    "safebrowsing.disable_download_protection": True
})

# Inicializar el navegador
service = Service(driver_path)
driver = webdriver.Chrome(service=service, options=chrome_options)

try:
    print("Accediendo al portal de Unicauca...")
    driver.get("https://www.unicauca.edu.co/bancoOferentes/principal.xhtml")
    
    # Esperar y completar formulario de login
    WebDriverWait(driver, 15).until(
        EC.presence_of_element_located((By.ID, "frmLogin:identificacion"))
    ).send_keys("59824341")
    
    driver.find_element(By.ID, "frmLogin:contrasena").send_keys("59824341")
    driver.find_element(By.NAME, "frmLogin:j_idt29").click()
    
    # Manejo especial de posibles alertas sin cerrar el navegador
    try:
        time.sleep(2)  # Espera breve para permitir que aparezca la alerta
        alert = driver.switch_to.alert
        alert_text = alert.text
        print(f"Alerta detectada: {alert_text}")
        if "contraseña" in alert_text.lower() or "seguridad" in alert_text.lower():
            alert.accept()  # Aceptar la alerta sin romper el flujo
            print("Alerta de seguridad aceptada, continuando...")
    except NoAlertPresentException:
        print("No se detectaron alertas, continuando normalmente")
    
    # Continuar con el proceso después del login
    print("Navegando a Gestión de periodos...")
    WebDriverWait(driver, 15).until(
        EC.element_to_be_clickable((By.XPATH, "//span[contains(text(), 'Gestión de periodos')]"))
    ).click()
    
    print("Abriendo último registro...")
    WebDriverWait(driver, 15).until(
        EC.element_to_be_clickable((By.XPATH, "(//button[contains(@title, 'Ver Detalles')])[last()]"))
    ).click()
    
    print("Accediendo a Oferentes postulados...")
    WebDriverWait(driver, 15).until(
        EC.element_to_be_clickable((By.XPATH, "//span[contains(text(), 'Oferentes postulados')]"))
    ).click()
    
    print("Exportando datos...")
    WebDriverWait(driver, 15).until(
        EC.element_to_be_clickable((By.XPATH, "//button[contains(@title, 'Exportar datos')]"))
    ).click()
    
    print("Esperando descarga del archivo...")
    time.sleep(10)  # Tiempo suficiente para la descarga
    
except Exception as e:
    print(f"Error durante la ejecución: {str(e)}")
    # Tomar captura de pantalla para diagnóstico
    driver.save_screenshot("error_screenshot.png")
    print("Se ha guardado una captura de pantalla del error")

finally:
    # Cerrar el navegador solo al final
    driver.quit()
    print("Proceso completado y navegador cerrado")