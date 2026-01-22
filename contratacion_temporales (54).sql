-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-01-2026 a las 22:31:01
-- Versión del servidor: 10.4.27-MariaDB
-- Versión de PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `contratacion_temporales`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

CREATE TABLE `asignaciones` (
  `id_asignacion` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `asignacion_mes` int(11) NOT NULL,
  `total_asignaciones` double DEFAULT NULL,
  `prima_navidad` int(11) DEFAULT NULL,
  `indemnizacion_vacaciones` int(11) DEFAULT NULL,
  `indemnizacion_prima_vac` int(11) DEFAULT NULL,
  `cesantias` int(11) DEFAULT NULL,
  `eps` int(11) DEFAULT NULL,
  `afp` int(11) DEFAULT NULL,
  `arp` int(11) DEFAULT NULL,
  `cajac` int(11) DEFAULT NULL,
  `icbf` int(11) DEFAULT NULL,
  `total_empleado` int(11) DEFAULT NULL,
  `total_entidad` int(11) DEFAULT NULL,
  `gran_total` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aspirante`
--

CREATE TABLE `aspirante` (
  `id_aspirante` int(11) NOT NULL,
  `fk_asp_doc_tercero` varchar(255) DEFAULT NULL,
  `fk_asp_periodo` varchar(100) DEFAULT NULL,
  `asp_estado` int(11) DEFAULT NULL,
  `asp_departamentos` text DEFAULT NULL,
  `asp_titulos` text DEFAULT NULL,
  `asp_telefono` varchar(100) DEFAULT NULL,
  `asp_celular` varchar(150) DEFAULT NULL,
  `asp_correo` varchar(255) DEFAULT NULL,
  `asp_trabaja_actual` varchar(255) DEFAULT NULL,
  `asp_cargo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deparmanentos`
--

CREATE TABLE `deparmanentos` (
  `PK_DEPTO` int(11) NOT NULL,
  `NOMBRE_DEPTO` varchar(150) NOT NULL,
  `NOMBRE_DEPTO_CORT` varchar(80) NOT NULL,
  `FK_FAC` int(11) NOT NULL,
  `depto_nom_propio` varchar(200) DEFAULT NULL,
  `trd_depto` varchar(80) DEFAULT NULL,
  `email_depto` varchar(180) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `depto_periodo`
--

CREATE TABLE `depto_periodo` (
  `id_depto_periodo` int(11) NOT NULL,
  `fk_depto_dp` int(11) DEFAULT NULL,
  `periodo` varchar(6) DEFAULT NULL,
  `dp_estado_catedra` varchar(2) DEFAULT NULL,
  `dp_estado_ocasional` varchar(2) DEFAULT NULL,
  `dp_estado_total` tinyint(1) DEFAULT NULL,
  `num_oficio_depto` varchar(80) DEFAULT NULL,
  `proyecta` varchar(100) DEFAULT NULL,
  `dp_folios` int(11) DEFAULT NULL,
  `dp_fecha_envio` timestamp NULL DEFAULT NULL,
  `fecha_oficio_depto` date DEFAULT NULL,
  `dp_acta_periodo` varchar(250) DEFAULT NULL,
  `dp_fecha_acta` date DEFAULT NULL,
  `dp_acepta_fac` varchar(10) DEFAULT NULL,
  `dp_observacion` text DEFAULT NULL,
  `dp_analisis` text DEFAULT NULL,
  `dp_devolucion` varchar(150) DEFAULT NULL,
  `dp_visado` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_vigencia`
--

CREATE TABLE `detalle_vigencia` (
  `id` int(11) NOT NULL,
  `vigencia_id` int(11) NOT NULL,
  `tipo_vinculacion` enum('CATEDRA','OCASIONAL') NOT NULL,
  `sede` int(11) NOT NULL,
  `saldo_inicial` decimal(18,2) NOT NULL,
  `saldo_actual` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facultad`
--

CREATE TABLE `facultad` (
  `PK_FAC` int(11) NOT NULL,
  `NOMBREC_FAC` varchar(60) NOT NULL,
  `NOMBREF_FAC` varchar(150) NOT NULL,
  `nombre_fac_min` varchar(100) DEFAULT NULL,
  `email_fac` varchar(100) DEFAULT NULL,
  `Nombre_fac_minb` varchar(60) NOT NULL,
  `decano` varchar(180) DEFAULT NULL,
  `trd_fac` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fac_periodo`
--

CREATE TABLE `fac_periodo` (
  `fp_id` int(11) NOT NULL,
  `fp_estado` tinyint(4) DEFAULT NULL,
  `fp_fk_fac` int(11) DEFAULT NULL,
  `fp_num_oficio` varchar(100) DEFAULT NULL,
  `fp_elaboro` varchar(250) DEFAULT NULL,
  `fp_decano` varchar(250) DEFAULT NULL,
  `fp_periodo` varchar(120) DEFAULT NULL,
  `fecha_accion` timestamp NULL DEFAULT NULL,
  `fecha_oficio_fac` date DEFAULT NULL,
  `fp_acepta_vra` int(11) DEFAULT NULL,
  `fp_obs_acepta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `glosas`
--

CREATE TABLE `glosas` (
  `id_glosa` int(11) NOT NULL,
  `version_glosa` int(11) DEFAULT NULL,
  `Tipo_glosa` varchar(255) DEFAULT NULL,
  `cantidad_glosas` int(11) DEFAULT NULL,
  `fk_dp_glosa` int(11) NOT NULL,
  `fk_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimiento`
--

CREATE TABLE `movimiento` (
  `id` int(11) NOT NULL,
  `oficio_id` int(11) NOT NULL,
  `tipo_movimiento` enum('CONTRATA','LIBERA') NOT NULL,
  `cdp` varchar(255) DEFAULT NULL,
  `valor` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oficio`
--

CREATE TABLE `oficio` (
  `id` int(11) NOT NULL,
  `numero` varchar(100) NOT NULL,
  `fecha` date NOT NULL,
  `detalle_vigencia_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oficios_glosas`
--

CREATE TABLE `oficios_glosas` (
  `id_oficio` int(11) NOT NULL,
  `fk_dp_glosa` int(11) NOT NULL,
  `version_glosa` int(11) NOT NULL,
  `numero_oficio` varchar(80) NOT NULL,
  `fecha_oficio` date DEFAULT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodo`
--

CREATE TABLE `periodo` (
  `id_periodo` int(11) NOT NULL,
  `nombre_periodo` varchar(100) DEFAULT NULL,
  `estado_periodo` tinyint(4) NOT NULL,
  `p_fecha_cierre` date DEFAULT NULL,
  `estado_novedad` tinyint(4) DEFAULT NULL,
  `p_fecha_cierr_nov` date DEFAULT NULL,
  `semanas_c` int(11) DEFAULT NULL,
  `inicio_sem` date DEFAULT NULL,
  `fin_sem` date DEFAULT NULL,
  `inicio_sem_oc` date DEFAULT NULL,
  `fin_sem_oc` date DEFAULT NULL,
  `plazo_jefe` date DEFAULT NULL,
  `plazo_fac` date DEFAULT NULL,
  `identif_necesidades` varchar(255) DEFAULT NULL,
  `distrib_visado` varchar(255) DEFAULT NULL,
  `plazo_vra` date DEFAULT NULL,
  `valor_punto` int(11) DEFAULT NULL,
  `smlv` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sede_origen`
--

CREATE TABLE `sede_origen` (
  `id_sede_origen` int(11) NOT NULL,
  `nombre_origen` varchar(255) NOT NULL,
  `obs_origen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id_solicitud` int(11) NOT NULL,
  `anio_semestre` varchar(10) NOT NULL,
  `facultad_id` int(11) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `tipo_docente` enum('Ocasional','Catedra') NOT NULL,
  `cedula` varchar(60) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `tipo_dedicacion` enum('TC','MT','') DEFAULT NULL,
  `tipo_dedicacion_r` varchar(10) DEFAULT NULL,
  `horas` decimal(11,1) DEFAULT NULL,
  `horas_r` decimal(11,1) DEFAULT NULL,
  `sede` varchar(150) DEFAULT NULL,
  `anexa_hv_docente_nuevo` enum('si','no','no aplica') DEFAULT NULL,
  `actualiza_hv_antiguo` enum('si','no','no aplica') DEFAULT NULL,
  `visado` tinyint(4) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `novedad` text DEFAULT NULL,
  `puntos` decimal(10,2) DEFAULT NULL,
  `s_observacion` text DEFAULT NULL,
  `tipo_reemplazo` varchar(255) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `anexos` varchar(255) DEFAULT NULL,
  `pregrado` varchar(255) DEFAULT NULL,
  `especializacion` varchar(255) DEFAULT NULL,
  `maestria` varchar(255) DEFAULT NULL,
  `doctorado` varchar(255) DEFAULT NULL,
  `otro_estudio` varchar(255) DEFAULT NULL,
  `experiencia_docente` varchar(255) DEFAULT NULL,
  `experiencia_profesional` varchar(255) DEFAULT NULL,
  `otra_experiencia` varchar(255) DEFAULT NULL,
  `id_novedad` int(11) DEFAULT NULL,
  `fecha_solicitud_sistema` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_history`
--

CREATE TABLE `solicitudes_history` (
  `history_id` int(11) NOT NULL,
  `id_solicitud_original` int(11) NOT NULL,
  `id_novedad_wc` int(11) DEFAULT NULL,
  `change_type` varchar(50) NOT NULL,
  `change_timestamp` datetime DEFAULT current_timestamp(),
  `anio_semestre` varchar(10) DEFAULT NULL,
  `facultad_id` int(11) DEFAULT NULL,
  `departamento_id` int(11) DEFAULT NULL,
  `tipo_docente` enum('Ocasional','Catedra') DEFAULT NULL,
  `cedula` varchar(60) DEFAULT NULL,
  `nombre` varchar(120) DEFAULT NULL,
  `tipo_dedicacion` enum('TC','MT','') DEFAULT NULL,
  `tipo_dedicacion_r` varchar(10) DEFAULT NULL,
  `horas` decimal(11,1) DEFAULT NULL,
  `horas_r` decimal(11,1) DEFAULT NULL,
  `sede` varchar(150) DEFAULT NULL,
  `anexa_hv_docente_nuevo` enum('si','no','no aplica') DEFAULT NULL,
  `actualiza_hv_antiguo` enum('si','no','no aplica') DEFAULT NULL,
  `visado` tinyint(4) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `novedad` text DEFAULT NULL,
  `puntos` decimal(10,2) DEFAULT NULL,
  `s_observacion` text DEFAULT NULL,
  `tipo_reemplazo` varchar(255) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `anexos` varchar(255) DEFAULT NULL,
  `pregrado` varchar(255) DEFAULT NULL,
  `especializacion` varchar(255) DEFAULT NULL,
  `maestria` varchar(255) DEFAULT NULL,
  `doctorado` varchar(255) DEFAULT NULL,
  `otro_estudio` varchar(255) DEFAULT NULL,
  `experiencia_docente` varchar(255) DEFAULT NULL,
  `experiencia_profesional` varchar(255) DEFAULT NULL,
  `otra_experiencia` varchar(255) DEFAULT NULL,
  `id_novedad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_novedades`
--

CREATE TABLE `solicitudes_novedades` (
  `id_novedad` int(11) NOT NULL,
  `facultad_id` int(11) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `periodo_anio` varchar(10) NOT NULL,
  `tipo_docente` enum('Ocasional','Catedra') NOT NULL,
  `tipo_usuario` varchar(20) NOT NULL,
  `tipo_novedad` enum('adicionar','modificar','eliminar') NOT NULL,
  `detalle_novedad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`detalle_novedad`)),
  `usuario_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `sn_acepta_fac` int(11) DEFAULT NULL,
  `sn_obs_fac` text DEFAULT NULL,
  `sn_id_envio_fac` tinyint(4) DEFAULT NULL,
  `sn_envio_fac_of` varchar(200) DEFAULT NULL,
  `sn_fecha_envio_fac` date DEFAULT NULL,
  `sn_elaboro_fac` varchar(255) DEFAULT NULL,
  `sn_acepta_vra` int(11) DEFAULT NULL,
  `sn_obs_vra` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_working_copy`
--

CREATE TABLE `solicitudes_working_copy` (
  `id_solicitud` int(11) NOT NULL,
  `anio_semestre` varchar(10) NOT NULL,
  `facultad_id` int(11) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `tipo_docente` enum('Ocasional','Catedra') NOT NULL,
  `cedula` varchar(60) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `tipo_dedicacion` enum('TC','MT','') DEFAULT NULL,
  `tipo_dedicacion_r` varchar(10) DEFAULT NULL,
  `tipo_dedicacion_inicial` varchar(10) DEFAULT NULL,
  `tipo_dedicacion_r_inicial` varchar(10) DEFAULT NULL,
  `horas` decimal(11,1) DEFAULT NULL,
  `horas_r` decimal(11,1) DEFAULT NULL,
  `horas_inicial` decimal(11,1) DEFAULT NULL,
  `horas_r_inicial` decimal(11,1) DEFAULT NULL,
  `sede` varchar(150) DEFAULT NULL,
  `anexa_hv_docente_nuevo` enum('si','no','no aplica') DEFAULT NULL,
  `actualiza_hv_antiguo` enum('si','no','no aplica') DEFAULT NULL,
  `visado` tinyint(4) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `novedad` text DEFAULT NULL,
  `puntos` decimal(10,2) DEFAULT NULL,
  `s_observacion` text DEFAULT NULL,
  `tipo_reemplazo` varchar(255) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `anexos` varchar(255) DEFAULT NULL,
  `pregrado` varchar(255) DEFAULT NULL,
  `especializacion` varchar(255) DEFAULT NULL,
  `maestria` varchar(255) DEFAULT NULL,
  `doctorado` varchar(255) DEFAULT NULL,
  `otro_estudio` varchar(255) DEFAULT NULL,
  `experiencia_docente` varchar(255) DEFAULT NULL,
  `experiencia_profesional` varchar(255) DEFAULT NULL,
  `otra_experiencia` varchar(255) DEFAULT NULL,
  `estado_depto` enum('PENDIENTE','ENVIADO') DEFAULT 'PENDIENTE',
  `oficio_depto` text DEFAULT NULL,
  `fecha_oficio_depto` date DEFAULT NULL,
  `oficio_con_fecha` varchar(255) DEFAULT NULL,
  `fecha_envio_depto` datetime DEFAULT NULL,
  `aprobador_depto_id` int(11) DEFAULT NULL,
  `estado_facultad` enum('PENDIENTE','APROBADO','RECHAZADO') DEFAULT 'PENDIENTE',
  `oficio_fac` varchar(255) DEFAULT NULL,
  `fecha_oficio_fac` date DEFAULT NULL,
  `oficio_con_fecha_fac` varchar(255) DEFAULT NULL,
  `observacion_facultad` text DEFAULT NULL,
  `fecha_aprobacion_facultad` datetime DEFAULT NULL,
  `aprobador_facultad_id` int(11) DEFAULT NULL,
  `estado_vra` enum('PENDIENTE','APROBADO','RECHAZADO') DEFAULT 'PENDIENTE',
  `observacion_vra` text DEFAULT NULL,
  `fecha_aprobacion_vra` datetime DEFAULT NULL,
  `aprobador_vra_id` int(11) DEFAULT NULL,
  `fk_id_solicitud_original` int(11) DEFAULT NULL COMMENT 'Referencia al id_solicitud en la tabla solicitudes',
  `archivado` tinyint(4) DEFAULT 0,
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tercero`
--

CREATE TABLE `tercero` (
  `id_tercero` int(11) NOT NULL,
  `documento_tercero` varchar(150) NOT NULL,
  `nombre_completo` varchar(450) NOT NULL,
  `apellido1` varchar(150) NOT NULL,
  `apellido2` varchar(150) DEFAULT NULL,
  `nombre1` varchar(150) NOT NULL,
  `nombre2` varchar(150) DEFAULT NULL,
  `fk_depto` int(11) DEFAULT NULL,
  `vincul` varchar(150) DEFAULT NULL,
  `sexo` char(1) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `vinculacion` varchar(3) DEFAULT NULL,
  `cargo_admin` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `escalafon` varchar(100) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `oferente_periodo` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `Id` int(11) NOT NULL,
  `DocUsuario` varchar(80) DEFAULT NULL,
  `Name` varchar(60) NOT NULL,
  `Email` varchar(160) NOT NULL,
  `Password` varchar(60) NOT NULL,
  `email_padre` varchar(160) DEFAULT NULL,
  `tipo_usuario` int(11) DEFAULT NULL,
  `fk_depto_user` int(11) DEFAULT NULL,
  `fk_fac_user` int(11) DEFAULT NULL,
  `u_nombre_en_cargo` varchar(255) DEFAULT NULL,
  `u_email_en_cargo` varchar(255) DEFAULT NULL,
  `u_tel_en_cargo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_conectados`
--

CREATE TABLE `usuarios_conectados` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `ultima_actividad` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vicerrectores`
--

CREATE TABLE `vicerrectores` (
  `id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `sexo` enum('F','M') NOT NULL,
  `encargo` enum('Propiedad','Encargado','Delegado') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vigencia`
--

CREATE TABLE `vigencia` (
  `id` int(11) NOT NULL,
  `anio` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vr_puntos`
--

CREATE TABLE `vr_puntos` (
  `id_puntos` int(11) NOT NULL,
  `vigencia` int(11) DEFAULT NULL,
  `valor_punto` int(11) DEFAULT NULL,
  `smlv` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `id_solicitud` (`id_solicitud`);

--
-- Indices de la tabla `aspirante`
--
ALTER TABLE `aspirante`
  ADD PRIMARY KEY (`id_aspirante`);

--
-- Indices de la tabla `deparmanentos`
--
ALTER TABLE `deparmanentos`
  ADD PRIMARY KEY (`PK_DEPTO`);

--
-- Indices de la tabla `depto_periodo`
--
ALTER TABLE `depto_periodo`
  ADD PRIMARY KEY (`id_depto_periodo`);

--
-- Indices de la tabla `detalle_vigencia`
--
ALTER TABLE `detalle_vigencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sede` (`sede`),
  ADD KEY `detalle_vigencia_ibfk_1` (`vigencia_id`);

--
-- Indices de la tabla `facultad`
--
ALTER TABLE `facultad`
  ADD PRIMARY KEY (`PK_FAC`);

--
-- Indices de la tabla `fac_periodo`
--
ALTER TABLE `fac_periodo`
  ADD PRIMARY KEY (`fp_id`);

--
-- Indices de la tabla `glosas`
--
ALTER TABLE `glosas`
  ADD PRIMARY KEY (`id_glosa`),
  ADD KEY `glosa_dp` (`fk_dp_glosa`);

--
-- Indices de la tabla `movimiento`
--
ALTER TABLE `movimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oficio_id` (`oficio_id`);

--
-- Indices de la tabla `oficio`
--
ALTER TABLE `oficio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detalle_vigencia_id` (`detalle_vigencia_id`);

--
-- Indices de la tabla `oficios_glosas`
--
ALTER TABLE `oficios_glosas`
  ADD PRIMARY KEY (`id_oficio`),
  ADD UNIQUE KEY `u_glosa_version` (`fk_dp_glosa`,`version_glosa`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indices de la tabla `periodo`
--
ALTER TABLE `periodo`
  ADD PRIMARY KEY (`id_periodo`);

--
-- Indices de la tabla `sede_origen`
--
ALTER TABLE `sede_origen`
  ADD PRIMARY KEY (`id_sede_origen`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `facultad_id` (`facultad_id`),
  ADD KEY `departamento_id` (`departamento_id`);

--
-- Indices de la tabla `solicitudes_history`
--
ALTER TABLE `solicitudes_history`
  ADD PRIMARY KEY (`history_id`);

--
-- Indices de la tabla `solicitudes_novedades`
--
ALTER TABLE `solicitudes_novedades`
  ADD PRIMARY KEY (`id_novedad`),
  ADD KEY `idx_facultad_departamento` (`facultad_id`,`departamento_id`),
  ADD KEY `idx_periodo_anio` (`periodo_anio`);

--
-- Indices de la tabla `solicitudes_working_copy`
--
ALTER TABLE `solicitudes_working_copy`
  ADD PRIMARY KEY (`id_solicitud`,`anio_semestre`,`departamento_id`);

--
-- Indices de la tabla `tercero`
--
ALTER TABLE `tercero`
  ADD PRIMARY KEY (`id_tercero`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `usuarios_conectados`
--
ALTER TABLE `usuarios_conectados`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `vicerrectores`
--
ALTER TABLE `vicerrectores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documento` (`documento`);

--
-- Indices de la tabla `vigencia`
--
ALTER TABLE `vigencia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `vr_puntos`
--
ALTER TABLE `vr_puntos`
  ADD PRIMARY KEY (`id_puntos`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `aspirante`
--
ALTER TABLE `aspirante`
  MODIFY `id_aspirante` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `depto_periodo`
--
ALTER TABLE `depto_periodo`
  MODIFY `id_depto_periodo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_vigencia`
--
ALTER TABLE `detalle_vigencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fac_periodo`
--
ALTER TABLE `fac_periodo`
  MODIFY `fp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `glosas`
--
ALTER TABLE `glosas`
  MODIFY `id_glosa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimiento`
--
ALTER TABLE `movimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `oficio`
--
ALTER TABLE `oficio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `oficios_glosas`
--
ALTER TABLE `oficios_glosas`
  MODIFY `id_oficio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `periodo`
--
ALTER TABLE `periodo`
  MODIFY `id_periodo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sede_origen`
--
ALTER TABLE `sede_origen`
  MODIFY `id_sede_origen` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_history`
--
ALTER TABLE `solicitudes_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_novedades`
--
ALTER TABLE `solicitudes_novedades`
  MODIFY `id_novedad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_working_copy`
--
ALTER TABLE `solicitudes_working_copy`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tercero`
--
ALTER TABLE `tercero`
  MODIFY `id_tercero` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios_conectados`
--
ALTER TABLE `usuarios_conectados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vicerrectores`
--
ALTER TABLE `vicerrectores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vigencia`
--
ALTER TABLE `vigencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vr_puntos`
--
ALTER TABLE `vr_puntos`
  MODIFY `id_puntos` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes` (`id_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_vigencia`
--
ALTER TABLE `detalle_vigencia`
  ADD CONSTRAINT `detalle_vigencia_ibfk_1` FOREIGN KEY (`vigencia_id`) REFERENCES `periodo` (`id_periodo`),
  ADD CONSTRAINT `detalle_vigencia_ibfk_2` FOREIGN KEY (`sede`) REFERENCES `sede_origen` (`id_sede_origen`);

--
-- Filtros para la tabla `glosas`
--
ALTER TABLE `glosas`
  ADD CONSTRAINT `glosa_dp` FOREIGN KEY (`fk_dp_glosa`) REFERENCES `depto_periodo` (`id_depto_periodo`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `movimiento`
--
ALTER TABLE `movimiento`
  ADD CONSTRAINT `movimiento_ibfk_1` FOREIGN KEY (`oficio_id`) REFERENCES `oficio` (`id`);

--
-- Filtros para la tabla `oficio`
--
ALTER TABLE `oficio`
  ADD CONSTRAINT `oficio_ibfk_1` FOREIGN KEY (`detalle_vigencia_id`) REFERENCES `detalle_vigencia` (`id`);

--
-- Filtros para la tabla `oficios_glosas`
--
ALTER TABLE `oficios_glosas`
  ADD CONSTRAINT `oficios_glosas_ibfk_1` FOREIGN KEY (`fk_dp_glosa`) REFERENCES `depto_periodo` (`id_depto_periodo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD CONSTRAINT `solicitudes_ibfk_1` FOREIGN KEY (`facultad_id`) REFERENCES `facultad` (`PK_FAC`),
  ADD CONSTRAINT `solicitudes_ibfk_2` FOREIGN KEY (`departamento_id`) REFERENCES `deparmanentos` (`PK_DEPTO`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
