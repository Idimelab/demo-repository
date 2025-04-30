-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-04-2025 a las 02:20:19
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `idem`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cita`
--

CREATE TABLE `cita` (
  `idcita` int(11) NOT NULL,
  `examen` varchar(45) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `codigo` int(11) DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `estado` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cita`
--

INSERT INTO `cita` (`idcita`, `examen`, `fecha`, `hora`, `codigo`, `observaciones`, `estado`) VALUES
(1, 'uroanalisis', '0000-00-00', '00:00:00', 12, 'observacion_1', 'pospuesta'),
(2, 'hemograma', '0000-00-00', '00:00:01', 1, 'normal', 'activa'),
(3, 'glicemia', '0000-00-00', '00:00:01', 3, 'ayuno', 'anulada'),
(101, '[perfil_lipidico]', '0000-00-00', '00:00:00', 0, '[ayuno]', '[activo]');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizacion`
--

CREATE TABLE `cotizacion` (
  `idcotizacion` int(11) NOT NULL,
  `codigo` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio` int(11) DEFAULT NULL,
  `preci_total` int(11) DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizacion`
--

INSERT INTO `cotizacion` (`idcotizacion`, `codigo`, `cantidad`, `precio`, `preci_total`, `observaciones`) VALUES
(1, 1, 1, 30000, 30000, 'observacion_ninguna'),
(2, 0, 2, 2, 20000, '40000');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen`
--

CREATE TABLE `examen` (
  `idexamen` int(11) NOT NULL,
  `tipo de examen` varchar(45) DEFAULT NULL,
  `codigo_de_examen` int(10) UNSIGNED DEFAULT NULL,
  `preparacion` varchar(500) DEFAULT NULL,
  `nombre_examen` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `examen`
--

INSERT INTO `examen` (`idexamen`, `tipo de examen`, `codigo_de_examen`, `preparacion`, `nombre_examen`) VALUES
(1, 'Revision_general', 1, 'Ninguna', 'Examen_general'),
(2, '[sangre]', 0, '[ayuno]', '[hemograma]'),
(3, '[sangre]', 0, '[ayuno]', '[hemograma]'),
(4, '[sangre]', 0, '[ayuno]', '[hemograma]'),
(5, '[sangre]', 0, '[ayuno]', '[hemograma]');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen_has_cita`
--

CREATE TABLE `examen_has_cita` (
  `examen_idexamen` int(11) NOT NULL,
  `cita_idcita` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen_has_cotizacion`
--

CREATE TABLE `examen_has_cotizacion` (
  `examen_idexamen` int(11) NOT NULL,
  `cotizacion_idcotizacion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `Idsuario` int(11) NOT NULL,
  `tip_usuario` varchar(45) DEFAULT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `apellido` varchar(45) DEFAULT NULL,
  `documento` varchar(45) DEFAULT NULL,
  `fecha_de_nacimiento` date DEFAULT NULL,
  `telefono` varchar(45) DEFAULT NULL,
  `direccion` varchar(45) DEFAULT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
  `eps` varchar(45) DEFAULT NULL,
  `examen_idexamen` int(11) NOT NULL,
  `cita_idcita` int(11) NOT NULL,
  `cotizacion_idcotizacion1` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`Idsuario`, `tip_usuario`, `nombre`, `apellido`, `documento`, `fecha_de_nacimiento`, `telefono`, `direccion`, `contrasena`, `eps`, `examen_idexamen`, `cita_idcita`, `cotizacion_idcotizacion1`) VALUES
(1, 'normal', 'Carlos', 'Sarmiento', '111023123', '0000-00-00', '3219988777', 'Cra 2da Num 4-28', 'Carlos123', 'Sanitas', 1, 1, 1),
(2, 'paciente', 'felipe', 'lopez', '65879412', '0000-00-00', '3165842587', 'calle 5 numero 45 56 ', '123456', 'salud total', 1, 1, 1),
(3, 'administrador', 'Valentina', 'Diaz', '1111523841', '0000-00-00', '3215847898', 'carrera 6 45-7', 'vd3456', 'famisanar', 1, 1, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cita`
--
ALTER TABLE `cita`
  ADD PRIMARY KEY (`idcita`);

--
-- Indices de la tabla `cotizacion`
--
ALTER TABLE `cotizacion`
  ADD PRIMARY KEY (`idcotizacion`);

--
-- Indices de la tabla `examen`
--
ALTER TABLE `examen`
  ADD PRIMARY KEY (`idexamen`);

--
-- Indices de la tabla `examen_has_cita`
--
ALTER TABLE `examen_has_cita`
  ADD PRIMARY KEY (`examen_idexamen`,`cita_idcita`),
  ADD KEY `fk_examen_has_cita_cita1_idx` (`cita_idcita`),
  ADD KEY `fk_examen_has_cita_examen1_idx` (`examen_idexamen`);

--
-- Indices de la tabla `examen_has_cotizacion`
--
ALTER TABLE `examen_has_cotizacion`
  ADD PRIMARY KEY (`examen_idexamen`,`cotizacion_idcotizacion`),
  ADD KEY `fk_examen_has_cotizacion_cotizacion1_idx` (`cotizacion_idcotizacion`),
  ADD KEY `fk_examen_has_cotizacion_examen1_idx` (`examen_idexamen`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`Idsuario`),
  ADD UNIQUE KEY `documento_UNIQUE` (`documento`),
  ADD KEY `fk_usuario_examen_idx` (`examen_idexamen`),
  ADD KEY `fk_usuario_cita1_idx` (`cita_idcita`),
  ADD KEY `fk_usuario_cotizacion2_idx` (`cotizacion_idcotizacion1`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cita`
--
ALTER TABLE `cita`
  MODIFY `idcita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT de la tabla `cotizacion`
--
ALTER TABLE `cotizacion`
  MODIFY `idcotizacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `examen`
--
ALTER TABLE `examen`
  MODIFY `idexamen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `Idsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `examen_has_cita`
--
ALTER TABLE `examen_has_cita`
  ADD CONSTRAINT `fk_examen_has_cita_cita1` FOREIGN KEY (`cita_idcita`) REFERENCES `cita` (`idcita`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_examen_has_cita_examen1` FOREIGN KEY (`examen_idexamen`) REFERENCES `examen` (`idexamen`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `examen_has_cotizacion`
--
ALTER TABLE `examen_has_cotizacion`
  ADD CONSTRAINT `fk_examen_has_cotizacion_cotizacion1` FOREIGN KEY (`cotizacion_idcotizacion`) REFERENCES `cotizacion` (`idcotizacion`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_examen_has_cotizacion_examen1` FOREIGN KEY (`examen_idexamen`) REFERENCES `examen` (`idexamen`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_cita1` FOREIGN KEY (`cita_idcita`) REFERENCES `cita` (`idcita`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_usuario_cotizacion2` FOREIGN KEY (`cotizacion_idcotizacion1`) REFERENCES `cotizacion` (`idcotizacion`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_usuario_examen` FOREIGN KEY (`examen_idexamen`) REFERENCES `examen` (`idexamen`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
