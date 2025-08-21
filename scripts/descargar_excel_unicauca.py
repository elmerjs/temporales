import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options

# Ruta a tu ChromeDriver
driver_path = 'C:/scripts/chromedriver-win64/chromedriver.exe'

# Dirección del proxy institucional
proxy_address = "http://proxy.unicauca.edu.co:3128"

# Configurar variables de entorno
os.environ["HTTP_PROXY"] = proxy_address
os.environ["HTTPS_PROXY"] = proxy_address
os.environ["NO_PROXY"] = "localhost,127.0.0.1"

# Opciones de Chrome
options = Options()
options.add_argument("--start-maximized")
options.add_argument(f"--proxy-server={proxy_address}")
options.add_argument('--proxy-bypass-list=localhost;127.0.0.1')

# Inicializa el driver
driver = webdriver.Chrome(service=Service(driver_path), options=options)

try:
    print("Cargando portal de Unicauca...")
    driver.get("https://www.unicauca.edu.co/bancoOferentes/principal.xhtml")

    # 2. Ingresar credenciales
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.ID, "frmLogin:identificacion"))
    )
    driver.find_element(By.ID, "frmLogin:identificacion").send_keys("59824341")
    driver.find_element(By.ID, "frmLogin:contrasena").send_keys("59824341")

    # 3. Clic en Ingresar
    driver.find_element(By.NAME, "frmLogin:j_idt29").click()

    # 3.1. Cerrar ventana emergente si aparece
    try:
        print("Verificando si aparece advertencia de contraseña insegura...")

        WebDriverWait(driver, 5).until(
            EC.presence_of_element_located((By.XPATH, "//button[normalize-space()='Aceptar']"))
        )

        # Intentar clic normal
        try:
            WebDriverWait(driver, 2).until(
                EC.element_to_be_clickable((By.XPATH, "//button[normalize-space()='Aceptar']"))
            ).click()
            print("Ventana emergente cerrada con clic normal.")
        except:
            print("No se pudo hacer clic normal, intentando con JavaScript...")
            driver.execute_script("""
                let botones = [...document.querySelectorAll('button')];
                let aceptar = botones.find(b => b.innerText.trim() === 'Aceptar');
                if (aceptar) aceptar.click();
            """)
            time.sleep(1)
            print("Ventana emergente cerrada con JavaScript.")

    except:
        print("No apareció ventana emergente, continuando...")

    # 4. Navegar a Gestión de Periodos
    WebDriverWait(driver, 15).until(
        EC.element_to_be_clickable((By.XPATH, "//span[contains(text(), 'Gestión de periodos')]"))
    ).click()

    # 5. Abrir el último registro
    WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.XPATH, "(//button[contains(@title, 'Ver Detalles')])[last()]"))
    ).click()

    # 6. Ir a Oferentes Postulados
    WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.XPATH, "//span[contains(text(), 'Oferentes postulados')]"))
    ).click()

    # 7. Exportar Datos
    WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.XPATH, "//button[contains(@title, 'Exportar datos')]"))
    ).click()

    print("Esperando descarga del archivo Excel...")
    time.sleep(5)

finally:
    driver.quit()
    print("Navegador cerrado.")
