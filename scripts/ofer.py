import os
import time
import pyautogui   # 👈 NUEVO: para mover el mouse y hacer clic
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.keys import Keys
from selenium.common.exceptions import TimeoutException
from webdriver_manager.chrome import ChromeDriverManager

# Configuración
DOWNLOAD_DIR = r"C:\DescargasAutomatizadas"
CREDENCIALES = {
    "usuario": "59824341",
    "clave": "59824341"
}

if not os.path.exists(DOWNLOAD_DIR):
    os.makedirs(DOWNLOAD_DIR)

def esperar_y_click(driver, selector, by=By.CSS_SELECTOR, timeout=15):
    """Espera y hace clic en un elemento"""
    WebDriverWait(driver, timeout).until(
        EC.element_to_be_clickable((by, selector))
    ).click()

# Configuración Chrome
chrome_options = Options()
chrome_options.add_argument("--start-maximized")
chrome_options.add_argument("--proxy-server=http://proxy.unicauca.edu.co:3128")  # 👈 Proxy aquí
chrome_options.add_argument("--ignore-certificate-errors")
chrome_options.add_argument("--proxy-bypass-list=127.0.0.1,localhost")

chrome_options.add_argument("--disable-features=PasswordManager,AutofillSaveCardBubble,ChromePasswordCheck")
chrome_options.add_experimental_option("prefs", {
    "download.default_directory": DOWNLOAD_DIR,a
    "download.prompt_for_download": False,
    "download.directory_upgrade": True,
    "safebrowsing.enabled": True
})

# Ejecución principal
try:
    print("🚀 Iniciando navegador...")
    driver = webdriver.Chrome(
        service=Service(ChromeDriverManager().install()),
        options=chrome_options
    )

    print("🌐 Accediendo al portal...")
    driver.get("https://www.unicauca.edu.co/bancoOferentes/principal.xhtml")

    print("🔑 Ingresando credenciales...")
    WebDriverWait(driver, 15).until(
        EC.presence_of_element_located((By.ID, "frmLogin:identificacion"))
    ).send_keys(CREDENCIALES["usuario"])
    driver.find_element(By.ID, "frmLogin:contrasena").send_keys(CREDENCIALES["clave"])
    driver.find_element(By.ID, "frmLogin:j_idt29").click()
    print("🖱️ Haciendo clic en 'Ingresar'...")

    # 👇 Esperamos que aparezca el popup de seguridad
    print("⚠️ Esperando popup de seguridad...")
    time.sleep(3)  # espera que aparezca el popup en pantalla
    # Mueve el mouse a la posición del botón Aceptar (ajusta las coordenadas)
    pyautogui.moveTo(1208, 454, duration=0.5)  # 👈 ajusta X, Y según tu pantalla
    pyautogui.click()
    print("✅ Popup de seguridad cerrado con clic en 'Aceptar'")

    # Ir a Gestión de periodos
    print("📅 Navegando a 'Gestión de periodos'...")
    gestion = WebDriverWait(driver, 15).until(
        EC.element_to_be_clickable((By.XPATH, "//a[contains(., 'Gestión de periodos')]"))
    )
    driver.execute_script("arguments[0].click();", gestion)

    # Esperar tabla de periodos
    print("⏳ Esperando que cargue la tabla de periodos...")
    WebDriverWait(driver, 20).until(
        EC.presence_of_element_located((By.ID, "formListadoPeriodos"))
    )

    # Abrir splitbutton
    print("📂 Abriendo menú del periodo activo...")
    split_btn = WebDriverWait(driver, 15).until(
        EC.element_to_be_clickable((By.ID, "formListadoPeriodos:listadoPeriodos:0:j_idt1521_menuButton"))
    )
    driver.execute_script("arguments[0].click();", split_btn)

    # Seleccionar Oferentes Postulados
    print("👥 Ingresando a 'Oferentes Postulados'...")
    opcion = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.ID, "formListadoPeriodos:listadoPeriodos:0:j_idt1525"))
    )
    opcion.click()

    # Exportar datos
    print("💾 Iniciando exportación a Excel...")
    esperar_y_click(driver, "#oferentesForm\\:j_idt1558 > div.panel.panel-success > div > input.btn.btn-primary")

    # Verificar descarga
    print("⏳ Esperando descarga... (30 segundos máximo)")
    tiempo_espera = 30
    while tiempo_espera > 0:
        archivos = [f for f in os.listdir(DOWNLOAD_DIR) if f.endswith(".xlsx")]
        if archivos:
            print(f"✅ Archivo descargado: {archivos[0]}")
            break
        time.sleep(1)
        tiempo_espera -= 1
    else:
        print("❌ No se encontró el archivo descargado")

except Exception as e:
    print(f"❌ ERROR CRÍTICO: {str(e)}")
    if "driver" in locals():
        driver.save_screenshot("error.png")
        print("📸 Captura de pantalla guardada como 'error.png'")
finally:
    if "driver" in locals():
        driver.quit()
        print("🛑 Navegador cerrado")
