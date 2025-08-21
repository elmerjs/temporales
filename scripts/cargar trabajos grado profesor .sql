LOAD DATA INFILE 'c:/scripts/trabajos_docencia_utf8.csv'
INTO TABLE datos_originales
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ';'
ENCLOSED BY '"'
LINES TERMINATED BY '\r\n'  -- en Windows es común que el salto de línea sea \r\n
IGNORE 1 ROWS;


/*CONSULTA  PARA EXTRAER AGRUPADO*/
SELECT 
       MAX(periodo) AS periodo,
       MAX(facultad) AS facultad,
       MAX(departamento) AS departamento,
       identificacion,
       MAX(apellidos_nombres) AS apellidos_nombres,
       MAX(nivel_estudios) AS nivel_estudios,
       MAX(tipo_contrato) AS tipo_contrato,
       MAX(dedicacion) AS dedicacion,
       COUNT(DISTINCT estudiantes_asesorados) AS cantidad_estudiantes_asesorados,
       GROUP_CONCAT(DISTINCT estudiantes_asesorados SEPARATOR ', ') AS estudiantes_asesorados,
       SUM(horas_semana) AS total_horas_semana,
       MAX(total_horas) AS total_horas,
       SUM(total_horas_estudiante) AS total_horas_estudiante,
       MAX(semanas) AS total_semanas
FROM datos_originales
GROUP BY identificacion
HAVING 
   COUNT(DISTINCT periodo) = 1 AND
   COUNT(DISTINCT facultad) = 1 AND
   COUNT(DISTINCT departamento) = 1 AND
   COUNT(DISTINCT apellidos_nombres) = 1 AND
   COUNT(DISTINCT nivel_estudios) = 1 AND
   COUNT(DISTINCT tipo_contrato) = 1 AND
   COUNT(DISTINCT dedicacion) = 1;