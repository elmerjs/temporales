import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options

# --- Configuración del Proxy ---
# Dirección del proxy institucional
proxy_address = "http://proxy.unicauca.edu.co:3128"

# Si tu proxy requiere usuario y contraseña, descomenta la siguiente línea
# y reemplaza 'tu_usuario' y 'tu_contraseña' con tus credenciales:
# proxy_address = "http://tu_usuario:tu_contraseña@proxy.unicauca.edu.co:3128"

# Configurar variables de entorno para que Python y otras librerías las usen.
# Esto es crucial para que las conexiones HTTP/HTTPS generales funcionen.
os.environ["HTTP_PROXY"] = proxy_address
os.environ["HTTPS_PROXY"] = proxy_address
# Esta línea le dice a Python que NO use el proxy para localhost y 127.0.0.1.
# Es fundamental para la comunicación interna entre Selenium y Chrome.
os.environ["NO_PROXY"] = "localhost,127.0.0.1"

# --- Opciones de Chrome para Selenium ---
# Ruta a tu ChromeDriver (asegúrate de que sea la correcta)
driver_path = 'C:/scripts/chromedriver-win64/chromedriver.exe'

options = Options()
options.add_argument("--start-maximized") # Inicia el navegador maximizado
options.add_argument(f"--proxy-server={proxy_address}") # Le dice a Chrome que use el proxy

# Esta opción le dice a Chrome que no use el proxy para las direcciones locales.
# Es vital para evitar el error de "localhost" con el proxy Squid.
options.add_argument("--proxy-bypass-list=localhost,127.0.0.1")

# Esta opción es útil para proxies que inspeccionan SSL y presentan certificados no estándar.
options.add_argument("--ignore-certificate-errors")

# Para evitar que el navegador se cierre inmediatamente si hay un error
options.add_experimental_option("detach", True)

# Inicializa el driver
print("🚀 Iniciando navegador...")
driver = webdriver.Chrome(service=Service(driver_path), options=options)

try:
    print("Cargando portal de Unicauca...")
    # La URL original que tu script intenta acceder
    driver.get("https://www.unicauca.edu.co/bancoOferentes/principal.xhtml")

    # 2. Ingresar credenciales
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.ID, "frmLogin:identificacion"))
    )
    driver.find_element(By.ID, "frmLogin:identificacion").send_keys("59824341")
    driver.find_element(By.ID, "frmLogin:contrasena").send_keys("59824341")

    # 3. Clic en Ingresar
    driver.find_element(By.NAME, "frmLogin:j_idt29").click()

    # 3.1. Cerrar ventana emergente si aparece (código para la advertencia de contraseña)
    try:
        print("Verificando si aparece advertencia de contraseña insegura...")
        WebDriverWait(driver, 5).until(
            EC.presence_of_element_located((By.XPATH, "//button[normalize-space()='Aceptar']"))
        )
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
            time.sleep(1) # Pequeña espera para que JavaScript actúe
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
    time.sleep(5) # Espera un tiempo prudente para la descarga

    print("Script ejecutado exitosamente.")

except Exception as e:
    print(f"❌ ERROR CRÍTICO: {e}")
    # En caso de error, puedes añadir más detalles para depuración si es necesario.
    # print(driver.page_source) # Descomenta para ver el HTML de la página en caso de error

finally:
    # Cierra el navegador al finalizar o si ocurre un error
    # Si quieres mantener el navegador abierto para depuración, puedes comentar driver.quit()
    # driver.quit()
    print("Navegador cerrado.")