<?php


             // Función para obtener el nombre de la facultad
    function obtenerIdFacultad($departamento_id)  {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT deparmanentos.FK_FAC  FROM deparmanentos WHERE PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['FK_FAC'];
        } else {
            return "Departamento Desconocido";
        }
    }

    // Función para obtener el nombre de la facultad
    function obtenerNombreFacultad($departamento_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT nombre_fac_min FROM facultad,deparmanentos WHERE
        PK_FAC = FK_FAC AND 
        deparmanentos.PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['nombre_fac_min'];
        } else {
            return "Facultad Desconocida";
        }
    }
 // Función para obtener el nombre del departamento
    function obtenerNombreDepartamento($departamento_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT depto_nom_propio FROM deparmanentos WHERE PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['depto_nom_propio'];
        } else {
            return "Departamento Desconocido";
        }
    }

function obtenerTRDDepartamento($departamento_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales_b');
        $sql = "SELECT trd_depto FROM deparmanentos WHERE PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['trd_depto'];
        } else {
            return "Departamento Desconocido";
        }
    }

function obtenerTRDFacultad($facultad_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales_b');
        $sql = "SELECT trd_fac FROM facultad WHERE PK_FAC = '$facultad_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['trd_fac'];
        } else {
            return "FAC Desconocido";
        }
    }
function validarCedulasEnPeriodo($cedulas, $anioSemestre) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    
    // Asegurarse de que la conexión fue exitosa
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    $cedulas_faltantes = [];
    foreach ($cedulas as $numDocumento) {
        $sql = "
            SELECT nombre_completo 
            FROM tercero 
            JOIN aspirante ON documento_tercero = fk_asp_doc_tercero
            WHERE documento_tercero = '$numDocumento'  
            AND LEFT(fk_asp_periodo, 4) = LEFT('$anioSemestre', 4)
        ";

        $result = $conn->query($sql);

        if ($result->num_rows == 0) {
            // Si no hay resultado, intenta recuperar el nombre para mostrarlo como faltante
            $sqlNombre = "SELECT nombre_completo FROM tercero WHERE documento_tercero = '$numDocumento'";
            $resultNombre = $conn->query($sqlNombre);

            if ($resultNombre->num_rows > 0) {
                $row = $resultNombre->fetch_assoc();
                $cedulas_faltantes[$numDocumento] = $row['nombre_completo'];
            } else {
                // Si tampoco hay nombre, usa un mensaje genérico
                $cedulas_faltantes[$numDocumento] = "Nombre no registrado";
            }
        }
    }

    $conn->close();
    return $cedulas_faltantes;
}

function datosProfesorCompleto(string $cedula, string $anioSemestre): array|false 
{
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "
        SELECT 
            t.email,
            t.nombre_completo,
            a.asp_departamentos,
            a.asp_titulos,
            a.asp_telefono,
            a.asp_celular,
            a.asp_correo,
            a.asp_trabaja_actual,
            a.asp_cargo
        FROM aspirante AS a
        JOIN tercero AS t ON t.documento_tercero = a.fk_asp_doc_tercero
        WHERE t.documento_tercero = ? 
          AND a.fk_asp_periodo = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("ss", $cedula, $anioSemestre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $datos = [
            'email' => $row['email'],
            'nombre_completo' => $row['nombre_completo'],
            'departamento' => $row['asp_departamentos'],
            'titulos' => $row['asp_titulos'],
            'telefono' => $row['asp_telefono'],
            'celular' => $row['asp_celular'],
            'correo' => $row['asp_correo'],
            'trabaja_actualmente' => $row['asp_trabaja_actual'],
            'cargo_actual' => $row['asp_cargo']
        ];
        $stmt->close();
        $conn->close();
        return $datos;
    }

    $stmt->close();
    $conn->close();
    return false;
}


function obtenerDeptoCerrado($depto, $periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT CASE 
                    WHEN dp_estado_catedra = 'CE' AND dp_estado_ocasional = 'CE' THEN 1 
                    ELSE 0 
                END AS resultado
            FROM depto_periodo
            WHERE fk_depto_dp = ? 
            AND periodo = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $depto, $periodo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['resultado'];
    } else {
        return 0; // Si no hay registros, devuelve 0 por defecto
    }
}
function existeSolicitudAnterior($cedula, $anio_semestre) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT EXISTS(
                SELECT 1 FROM solicitudes 
                WHERE cedula = ? 
                AND anio_semestre < ?
            ) AS existe";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $cedula, $anio_semestre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (bool) $row['existe']; // Retorna TRUE si existe, FALSE si no
    } else {
        return false;
    }
}
function obtenerCatedraCerrado($depto, $periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT CASE 
                    WHEN dp_estado_catedra = 'CE' THEN 1 
                    ELSE 0 
                END AS resultado
            FROM depto_periodo
            WHERE fk_depto_dp = ? 
            AND periodo = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $depto, $periodo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['resultado'];
    } else {
        return 0; // Si no hay registros, devuelve 0 por defecto
    }
}

function obtenerOcasionalCerrado($depto, $periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT CASE 
                    WHEN dp_estado_ocasional = 'CE' THEN 1 
                    ELSE 0 
                END AS resultado
            FROM depto_periodo
            WHERE fk_depto_dp = ? 
            AND periodo = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $depto, $periodo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['resultado'];
    } else {
        return 0; // Si no hay registros, devuelve 0 por defecto
    }
}

function obtenerperiodo($anio_semestre) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT periodo.estado_periodo FROM periodo WHERE nombre_periodo = '$anio_semestre'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['estado_periodo'];
    } else {
        return "Período Desconocido";
    }
}
function obtener_acta($periodo, $fk_depto) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    
    // Verificar conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    $sql = "SELECT dp_acta_periodo, dp_fecha_acta 
            FROM depto_periodo 
            WHERE fk_depto_dp = ? AND periodo = ? 
            GROUP BY fk_depto_dp";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $fk_depto, $periodo); // "i" para entero, "s" para string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'acta_periodo' => $row['dp_acta_periodo'],
            'fecha_acta' => $row['dp_fecha_acta']
        ];
    } else {
        return 0;
    }
}
function obtener_numero_acta($periodo, $fk_depto) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    
    // Verificar la conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Consulta SQL para obtener el número de acta
    $sql = "SELECT dp_acta_periodo 
            FROM depto_periodo 
            WHERE fk_depto_dp = ? AND periodo = ? 
            GROUP BY fk_depto_dp";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $fk_depto, $periodo);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si se obtuvo un resultado
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Retornar el número de acta si no es NULL, de lo contrario, retornar 0
        return $row['dp_acta_periodo'] !== null ? $row['dp_acta_periodo'] : 0;
    } else {
        return 0;
    }
}
function obtenerperiodonov($anio_semestre) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT periodo.estado_novedad FROM periodo WHERE nombre_periodo = '$anio_semestre'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['estado_novedad'];
    } else {
        return "Período nov Desconocido";
    }
}

function contarSolicitudesNoAnuladas($anio_semestre) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    
    // Preparamos la consulta con parámetros para evitar inyección SQL
    $sql = "SELECT COUNT(*) as total FROM `solicitudes` 
            WHERE solicitudes.anio_semestre = ? 
            AND (solicitudes.estado IS NULL OR solicitudes.estado <> 'an')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $anio_semestre);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total'];
    } else {
        return 0;
    }
    
    $stmt->close();
    $conn->close();
}

function obteneridfac($departamento_id) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT deparmanentos.FK_FAC FROM deparmanentos WHERE PK_DEPTO = '$departamento_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['FK_FAC'];
    } else {
        return "IDFAC Desconocida";
    }
}
function obtenerenviof($fp_fk_fac, $fp_periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT fac_periodo.fp_estado FROM fac_periodo WHERE fp_fk_fac = '$fp_fk_fac' and fp_periodo = '$fp_periodo'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['fp_estado'];
    } else {
        return "periodo_fac Desconocido";
    }
}

function obteneraceptacionfac($departamento_id, $dp_periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT depto_periodo.dp_acepta_fac FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' and periodo = '$dp_periodo'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['dp_acepta_fac'];
    } else {
        return "aceptacion Desconocida";
    }
}

function obtenerAceptacionFacultadfull($facultad_id, $dp_periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    
    // Verificamos si la conexión fue exitosa
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
    
    // Consulta para contar los registros según el estado de aceptación
    $sql = "
        SELECT 
            SUM(CASE WHEN depto_periodo.dp_acepta_fac = 'aceptar' THEN 1 ELSE 0 END) AS total_aceptados,
            COUNT(*) AS total_departamentos
        FROM depto_periodo
        JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
        WHERE depto_periodo.periodo = '$dp_periodo' AND deparmanentos.FK_FAC = '$facultad_id'
    ";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        $total_aceptados = (int)$row['total_aceptados'];
        $total_departamentos = (int)$row['total_departamentos'];

        if ($total_aceptados === $total_departamentos) {
            // Todas las filas están en "aceptar"
            return 2;
        } elseif ($total_aceptados > 0) {
            // Al menos una fila está en "aceptar"
            return 1;
        } else {
            // Ninguna fila está en "aceptar"
            return 0;
        }
    } else {
        // No hay resultados o un error en la consulta
        return "Aceptación desconocida";
    }
}
function obtenerobs_fac($departamento_id, $dp_periodo) {
    // Conexión a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');

    // Verificar la conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Consulta SQL
    $sql = "SELECT dp_observacion, dp_fecha_envio
                FROM depto_periodo
                WHERE fk_depto_dp = '$departamento_id' AND periodo = '$dp_periodo'";
    $result = $conn->query($sql);

    // Procesar resultados
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Obtener valores
             
        $observacion = $row['dp_observacion'] !== null ? nl2br(htmlspecialchars($row['dp_observacion'])) : "Observación desconocida";

        
        $fechaEnvio = $row['dp_fecha_envio'] !== null ? $row['dp_fecha_envio'] : "Fecha desconocida";

        // Retornar la observación junto con la fecha
        return "$observacion (Enviado el $fechaEnvio)";
    } else {
        // No se encontraron resultados
        return "Aceptación desconocida";
    }
}
function plazo_jefe($periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');

    // Verificar la conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Preparar la consulta para evitar inyección SQL
    $stmt = $conn->prepare("SELECT plazo_jefe FROM periodo WHERE nombre_periodo = ?");
    $stmt->bind_param("s", $periodo);
    $stmt->execute();
    $stmt->bind_result($plazo_jefe);
    $stmt->fetch();

    $stmt->close();
    $conn->close();

    return $plazo_jefe ?? "Plazo desconocido"; // Si no hay resultado, devuelve "Plazo desconocido"
}
function plazo_fac($periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');

    // Verificar la conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Preparar la consulta para evitar inyección SQL
    $stmt = $conn->prepare("SELECT plazo_fac FROM periodo WHERE nombre_periodo = ?");
    $stmt->bind_param("s", $periodo);
    $stmt->execute();
    $stmt->bind_result($plazo_fac);
    $stmt->fetch();

    $stmt->close();
    $conn->close();

    return $plazo_fac ?? "Plazo desconocido"; // Si no hay resultado, devuelve "Plazo desconocido"
}
function plazo_vra($periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');

    // Verificar la conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Preparar la consulta para evitar inyección SQL
    $stmt = $conn->prepare("SELECT plazo_vra FROM periodo WHERE nombre_periodo = ?");
    $stmt->bind_param("s", $periodo);
    $stmt->execute();
    $stmt->bind_result($plazo_vra);
    $stmt->fetch();

    $stmt->close();
    $conn->close();

    return $plazo_vra ?? "Plazo desconocido"; // Si no hay resultado, devuelve "Plazo desconocido"
}
function obteneraceptacionvra($fp_fk_fac, $fp_periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT fac_periodo.fp_acepta_vra FROM fac_periodo WHERE fp_fk_fac = '$fp_fk_fac' and fp_periodo = '$fp_periodo'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['fp_acepta_vra'];
    } else {
        return "aceptacion Desconocida";
    }
}
function obtenerobsaceptacionvra($fp_fk_fac, $fp_periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT fac_periodo.fp_obs_acepta FROM fac_periodo WHERE fp_fk_fac = '$fp_fk_fac' and fp_periodo = '$fp_periodo'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['fp_obs_acepta'];
    } else {
        return "aceptacion Desconocida";
    }
}

// Función para verificar si las cédulas ya existen en la base de datos
function cedulasExistentes($conn, $anio_semestre, $departamento_id, $cedulas) {
    $cedulas_placeholder = implode(',', array_fill(0, count($cedulas), '?'));
    $stmt = $conn->prepare("SELECT cedula FROM solicitudes WHERE anio_semestre = ? AND departamento_id = ? AND cedula IN ($cedulas_placeholder) AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)");
    $params = array_merge([$anio_semestre, $departamento_id], $cedulas);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $existentes = [];
    while ($row = $result->fetch_assoc()) {
        $existentes[] = $row['cedula'];
    }
    return $existentes;
}
// Función para verificar si las cédulas ya existen en la base de datos
// Función para verificar si las cédulas ya existen en la base de datos  excluye 222
function cedulasExistentesall($conn, $anio_semestre, $departamento_id, $cedulas) {
    // Filtrar el arreglo para excluir el valor '222'
    $cedulas = array_filter($cedulas, function($cedula) {
        return $cedula !== '222';
    });

    // Si no quedan cédulas después del filtro, devolver un arreglo vacío
    if (empty($cedulas)) {
        return [];
    }

    // Crear los placeholders dinámicos
    $cedulas_placeholder = implode(',', array_fill(0, count($cedulas), '?'));

    // Preparar la consulta
    $stmt = $conn->prepare("
        SELECT DISTINCT solicitudes.cedula, deparmanentos.depto_nom_propio 
        FROM solicitudes 
        JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id 
        WHERE solicitudes.anio_semestre = ? 
        AND solicitudes.cedula IN ($cedulas_placeholder) AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
    ");

    // Vincular los parámetros
    $params = array_merge([$anio_semestre], $cedulas);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);

    // Ejecutar la consulta y procesar los resultados
    $stmt->execute();
    $result = $stmt->get_result();
    $existentes = [];
    while ($row = $result->fetch_assoc()) {
        $existentes[] = [
            'cedula' => $row['cedula'],
            'departamento_nombre' => $row['depto_nom_propio']
        ];
    }

    return $existentes;
}


function verificaVisados($anio_semestre, $departamento_id, $tipo_docente) {
    // Conectar a la base de datos
    $conn = new mysqli("localhost", "root", "", "contratacion_temporales");

    // Verificar conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Escapar las variables para evitar inyecciones SQL
    $anio_semestre = $conn->real_escape_string($anio_semestre);
    $departamento_id = $conn->real_escape_string($departamento_id);
    $tipo_docente = $conn->real_escape_string($tipo_docente);

    // Consulta SQL
    $sql = "
    SELECT 
        CASE 
            WHEN COUNT(*) = (
                SELECT COUNT(*) 
                FROM solicitudes 
                WHERE anio_semestre = '$anio_semestre' 
                  AND departamento_id = $departamento_id 
                  AND tipo_docente = '$tipo_docente'
                  AND visado = 1
                  AND (estado <> 'an' OR estado IS NULL)
            ) 
            THEN '1' 
            ELSE '0' 
        END AS resultado
    FROM solicitudes 
    WHERE anio_semestre = '$anio_semestre' 
      AND departamento_id = $departamento_id 
      AND tipo_docente = '$tipo_docente' 
      AND (estado <> 'an' OR estado IS NULL);
";

    // Ejecutar consulta
    $result = $conn->query($sql);

    // Verificar si se obtuvo un resultado
    if ($result && $row = $result->fetch_assoc()) {
        $resultado = $row['resultado'];
    } else {
        $resultado = '0'; // Valor por defecto en caso de error
    }

    // Cerrar conexión
    $conn->close();

    // Devolver resultado
    return $resultado;
}
function obtenerDecano($fk_fac) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT decano FROM facultad WHERE PK_FAC = '$fk_fac'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['decano'];
        } else {
            return "fac Desconocido";
        }
    }
function obtenernombrefac($id_facultad) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT nombre_fac_minb FROM facultad WHERE PK_FAC = '$id_facultad'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['nombre_fac_minb'];
        } else {
            return "fac Desconocido";
        }
    }
function obteneremailfac($id_facultad) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT email_fac FROM facultad WHERE PK_FAC = '$id_facultad'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['email_fac'];
        } else {
            return "fac Desconocido";
        }
    }
function obtenernombredepto($departamento_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT depto_nom_propio FROM deparmanentos WHERE PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['depto_nom_propio'];
        } else {
            return "fac Desconocido";
        }
    }
function obtenerobs_vra($id_facultad, $anio_periodo) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT fac_periodo.fp_obs_acepta FROM fac_periodo WHERE fac_periodo.fp_fk_fac = '$id_facultad' AND fac_periodo.fp_periodo = '$anio_periodo'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['fp_obs_acepta'] ? $row['fp_obs_acepta'] : ""; // Verifica si es null o vacío
    } else {
        return ""; // Si no hay resultados, devuelve una cadena vacía
    }
}

?>