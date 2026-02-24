-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-02-2026 a las 20:13:17
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ordencompra2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `firmas_orden`
--

CREATE TABLE `firmas_orden` (
  `Id` int(11) NOT NULL,
  `Usuario_Id` int(11) DEFAULT NULL,
  `Orden_Id` int(11) DEFAULT NULL,
  `Fecha_Firma` datetime DEFAULT current_timestamp(),
  `Decision` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `funcionarios_muni`
--

CREATE TABLE `funcionarios_muni` (
  `ID` int(11) NOT NULL,
  `RUT` varchar(25) NOT NULL,
  `NOMBRE` varchar(250) NOT NULL,
  `APELLIDO` varchar(250) NOT NULL,
  `TIPO_CONTRATO` varchar(250) NOT NULL,
  `ESCALAFON` varchar(250) NOT NULL,
  `GRADO` int(11) NOT NULL,
  `SEXO` varchar(50) NOT NULL,
  `FECHA_INGRESO` date NOT NULL,
  `FECHA_TERMINO` date DEFAULT NULL,
  `DEPTO` varchar(250) NOT NULL,
  `CARGO` varchar(250) NOT NULL,
  `FUNCIONES` varchar(300) NOT NULL,
  `CORREO` varchar(400) DEFAULT NULL,
  `TITULO_PROF` varchar(250) DEFAULT NULL,
  `FONO` varchar(250) DEFAULT NULL,
  `DEPENDE` varchar(150) DEFAULT NULL,
  `ADQUISICIONES` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `funcionarios_muni`
--

INSERT INTO `funcionarios_muni` (`ID`, `RUT`, `NOMBRE`, `APELLIDO`, `TIPO_CONTRATO`, `ESCALAFON`, `GRADO`, `SEXO`, `FECHA_INGRESO`, `FECHA_TERMINO`, `DEPTO`, `CARGO`, `FUNCIONES`, `CORREO`, `TITULO_PROF`, `FONO`, `DEPENDE`, `ADQUISICIONES`) VALUES
(1, '3234681-2', 'Jose Francisco ', 'Saez Rivas', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, NULL, NULL),
(2, '5835979-3', 'Segundo Juan ', 'Beltran Vines', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, NULL, NULL),
(3, '6277176-3', 'Carlos Alberto ', 'Zapata Muñoz', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'DIDECO', NULL),
(4, '7421368-5', 'Lilian Anita ', 'Cabalin Carrasco', 'Planta', 'Directivo', 8, 'F', '1986-11-03', NULL, 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', NULL, NULL, NULL, 'ALCALDIA', 'DIRECTOR'),
(5, '7422315-K', 'Luis Aldo ', 'Cid Anguita', 'Planta', 'Directivo', 8, 'M', '1995-07-24', NULL, 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', NULL, NULL, NULL, 'ALCALDIA', 'DIRECTOR'),
(6, '7763220-4', 'Rodolfo Patricio ', 'Espinoza Fuentes', 'Planta', 'Directivo', 8, 'M', '2015-09-01', '2024-04-30', 'CONTROL INTERNO', 'CONTROL INTERNO', 'CONTROL INTERNO', NULL, NULL, NULL, NULL, 'DIRECTOR'),
(7, '7786851-8', 'Jose ', 'Cea Gatica', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, NULL, NULL),
(8, '8105630-7', 'Felismon Alberto ', 'Almendras Jaque', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, NULL, NULL),
(9, '8304921-9', 'Rodrigo Mariano ', 'Tapia Avello', 'Planta', 'Alcalde', 6, 'M', '2021-06-29', '2024-12-05', 'ALCALDE', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, NULL, 'ALCALDE'),
(10, '8349487-5', 'Washington Luis ', 'Rioseco Gutierrez', 'Planta', 'Directivo', 8, 'M', '2001-10-01', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'ALCALDIA', 'SUPER_ADQUI'),
(11, '8579103-6', 'Emilio Antonio ', 'Padilla Gonzalez', 'Contrata', 'Auxiliar', 18, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, 'ADMIN', NULL),
(12, '8604024-7', 'Luis Dagoberto ', 'Almendras Jaque', 'Planta', 'Auxiliar', 13, 'M', '1988-01-04', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'ADMIN', 'ADQUISICIONES'),
(13, '8763124-9', 'Leonardo ', 'Diaz Seguel', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, NULL, NULL),
(14, '8987851-9', 'Victor Manuel ', 'Mellado Alvarez', 'Planta', 'Auxiliar', 15, 'M', '1998-10-01', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'ADMIN', 'ADQUISICIONES'),
(15, '9141120-2', 'Norma Rosa ', 'Fierro Palma', 'Planta', 'Directivo', 9, 'F', '1998-11-01', NULL, 'TRANSITO', 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', NULL, NULL, NULL, 'ALCALDIA', 'DIRECTOR'),
(16, '9460547-4', 'Mario Enrique ', 'Dominguez Espinoza', 'Planta', 'Auxiliar', 13, 'M', '1988-01-04', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'ADMIN', NULL),
(17, '9752874-8', 'Sergio Hernan ', 'Jara Almendras', 'Planta', 'Auxiliar', 14, 'M', '1998-10-01', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'ADMIN', NULL),
(18, '9753735-6', 'Fresia Del Carmen', 'Ruiz Campos', 'Planta', 'Administrativo', 13, 'F', '1988-01-04', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'DAF', NULL),
(19, '9820806-2', 'Omar Antonio', 'San Martin Novoa', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, NULL, NULL),
(20, '9884720-0', 'Angelica Zunilda ', 'Sepulveda Almendras', 'Contrata', 'Administrativo', 14, 'F', '2003-07-11', NULL, 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', NULL, NULL, NULL, 'SECRETARIA', NULL),
(21, '9903302-9', 'Jeannette ', 'Munoz Sepulveda', 'Planta', 'Directivo', 8, 'F', '2003-01-06', NULL, 'JUZGADO POLICIA LOCAL', 'JUZGADO POLICIA LOCAL', 'JUZGADO POLICIA LOCAL', NULL, NULL, NULL, NULL, 'DIRECTOR'),
(22, '10154318-8', 'Sergio Daniel ', 'Espinoza Almendras', 'Contrata', 'Administrativo', 13, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, 'ADMIN', NULL),
(23, '10348970-9', 'Jorge Antonio ', 'Morales Morales', 'Contrata', 'Auxiliar', 16, 'M', '2015-06-22', NULL, 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', NULL, NULL, NULL, 'TRANSITO', NULL),
(24, '10426003-9', 'Rumelio Fernando ', 'Rios Bustos', 'Contrata', 'Auxiliar', 18, 'M', '2018-01-09', NULL, 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', NULL, NULL, NULL, 'OBRAS', NULL),
(25, '10470784-K', 'Manuel Alejo ', 'Araneda Salas', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, NULL, NULL),
(26, '10477992-1', 'Sergio Adolfo ', 'Venegas Alvarez', 'Planta', 'Administrativo', 13, 'M', '1994-01-03', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'TRANSITO', NULL),
(27, '10490233-2', 'Patricio Rene ', 'Riffo Guerrero', 'Contrata', 'Auxiliar', 18, 'M', '2012-10-01', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'ADMIN', NULL),
(28, '10814753-9', 'Daniel Antonio ', 'Crisostomo Guerrero', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, NULL, NULL),
(29, '11079403-7', 'Maria Caterina ', 'Riquelme Salazar', 'Contrata', 'Auxiliar', 18, 'F', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'DIDECO', NULL),
(30, '11154081-0', 'Sandra Liliana ', 'Bobadilla Cisterna', 'Planta', 'Directivo', 8, 'F', '2021-07-01', '2024-08-05', 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, NULL, 'DIRECTOR'),
(31, '11417113-1', 'Ana Gloria ', 'Almendras Jaque', 'Planta', 'Administrativo', 13, 'F', '1996-01-12', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'DAF', NULL),
(32, '11417236-7', 'Elier Alejandro ', 'Merino Maldonado', 'Contrata', 'Auxiliar', 18, 'M', '2013-12-16', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'ADMIN', NULL),
(33, '11575318-5', 'Yolanda Antonieta ', 'Medina Aillapan', 'Planta', 'Administrativo', 15, 'F', '2000-11-13', NULL, 'JUZGADO POLICIA LOCAL', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'DAF', NULL),
(34, '11792980-9', 'Juan Domingo ', 'Crisostomo Guerrero', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, NULL, NULL),
(35, '11793809-3', 'Essem Maria Cecilia', 'Abuter Riquelme', 'Planta', 'Directivo', 9, 'F', '1994-10-05', NULL, 'DIDECO', 'DEPTO SOCIAL', 'DEPTO SOCIAL', NULL, NULL, NULL, 'ALCALDIA', 'DIRECTOR'),
(36, '12141276-4', 'Francisco Enrique ', 'Escobar Morales', 'Contrata', 'Auxiliar', 18, 'M', '2016-03-01', NULL, 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', NULL, NULL, NULL, 'ADMIN', NULL),
(37, '12325524-0', 'Salma Viviana ', 'Rifo Cruzat', 'Codigo del Trabajo', 'C.T.', 0, 'F', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, NULL, NULL),
(38, '12325641-7', 'Daniel ', 'Contreras Moya', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2024-01-01', NULL, 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', NULL, NULL, NULL, NULL, NULL),
(39, '12326831-8', 'Isabel Alejandra ', 'Jara Rozas', 'Planta', 'Profesional', 10, 'F', '2009-09-01', NULL, 'SOCIAL', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'SOCIAL', NULL),
(40, '12556819-K', 'Benedicto Apolonio ', 'Ruiz Campos', 'Contrata', 'Auxiliar', 18, 'M', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'TRANSITO', NULL),
(41, '12556976-5', 'Antonio Bernabe ', 'Parra Gutierrez', 'Planta', 'Auxiliar', 16, 'M', '2011-01-10', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'ADMIN', NULL),
(42, '12769017-0', 'Juan Carlos ', 'Almendras Bascur', 'Planta', 'Auxiliar', 16, 'M', '1996-04-01', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'DAF', NULL),
(43, '13387065-2', 'Carlos Alberto ', 'Herrera Sepulveda', 'Contrata', 'Profesional', 10, 'M', '2022-02-01', NULL, 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', NULL, NULL, NULL, 'OBRAS', NULL),
(44, '13626270-K', 'Gladys ', 'Morales Morales', 'Contrata', 'Auxiliar', 18, 'F', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'DIDECO', NULL),
(45, '13802045-2', 'Osman Victor ', 'Zapata Altamirano', 'Contrata', 'Auxiliar', 18, 'M', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'TRANSITO', NULL),
(46, '14069957-8', 'Noelia Marisol ', 'Melo Urrutia', 'Contrata', 'Auxiliar', 18, 'F', '2023-01-01', NULL, 'DIREC. ADM. Y FINANZAS', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'SOCIAL', NULL),
(47, '14069978-0', 'Carola Alejandra ', 'Sanchez Parra', 'Contrata', 'Administrativo', 12, 'F', '2023-01-01', NULL, 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', NULL, NULL, NULL, 'ADMIN', NULL),
(48, '14274191-1', 'Hector Manases ', 'Linay Ibañez', 'Contrata', 'Auxiliar', 18, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, 'ADMIN', NULL),
(49, '14274269-1', 'Pamela Eduviges ', 'Ortega Almendras', 'Planta', 'Administrativo', 14, 'F', '1998-08-03', NULL, 'DIREC. ADM. Y FINANZAS', 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', NULL, NULL, NULL, 'DAF', NULL),
(50, '14349660-0', 'Eric Ariel ', 'Fuentes Arriagada', 'Contrata', 'Administrativo', 12, 'M', '2023-01-01', '2024-12-05', 'SECPLAC', 'SECPLAC', 'SECPLAC', NULL, NULL, NULL, NULL, NULL),
(51, '14350127-2', 'Sebastian Antonio ', 'Cuevas Ormeño', 'Contrata', 'Administrativo', 12, 'M', '2023-01-01', NULL, 'SECPLAN', 'SECPLAC', 'SECPLAC', NULL, NULL, NULL, 'SECPLAN', NULL),
(52, '14597181-0', 'Susana ', 'San Martin villanueva', 'Contrata', 'Auxiliar', 18, 'F', '2023-01-01', NULL, 'DEPTO SOCIAL', 'DEPTO SOCIAL', 'DEPTO SOCIAL', NULL, NULL, NULL, 'DIDECO', NULL),
(53, '15189985-4', 'Ana Karen ', 'Rodriguez Araneda', 'Contrata', 'Profesional', 10, 'F', '2017-03-01', NULL, 'SOCIAL', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'SOCIAL', NULL),
(54, '15205613-3', 'Jenny Paola ', 'Aburto Aburto', 'Contrata', 'Administrativo', 15, 'F', '2014-01-02', NULL, 'DIREC. ADM. Y FINANZAS', 'SECPLAC', 'SECPLAC', NULL, NULL, NULL, 'DAF', NULL),
(55, '15206808-5', 'Jaime Arturo ', 'Valenzuela Ortiz', 'Contrata', 'Administrativo', 12, 'M', '2023-01-01', NULL, 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', 'DIREC. ADM. Y FINANZAS', NULL, NULL, NULL, 'TRANSITO', NULL),
(56, '15207636-3', 'Paulina ', 'Vial Iraira', 'Contrata', 'Auxiliar', 18, 'F', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'DIDECO', NULL),
(57, '15498762-2', 'Soledad Maribel ', 'Jara Matamala', 'Planta', 'Jefatura', 10, 'F', '2023-10-09', NULL, 'DIREC. ADM. Y FINANZAS', 'TESORERIA - CAJA', 'TESORERIA - CAJA', NULL, NULL, NULL, 'DAF', NULL),
(58, '15498819-K', 'Jorge Patricio ', 'Beroiza Bascur', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2023-01-01', NULL, 'ALCALDIA', 'ALCALDIA', 'ALCALDIA', NULL, NULL, NULL, NULL, NULL),
(59, '15518917-7', 'Freddy Paolo ', 'Baez Martinez', 'Contrata', 'Profesional', 10, 'M', '2023-02-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'DIDECO', NULL),
(60, '15626382-6', 'Claudia Pilar ', 'Belmar Sandoval', 'Contrata', 'Administrativo', 17, 'F', '2023-02-01', NULL, 'SOCIAL', 'SECPLAC', 'SECPLAC', NULL, NULL, NULL, 'SOCIAL', NULL),
(61, '15704213-0', 'Carolina Alejandra ', 'Jimenez Molina', 'Contrata', 'Profesional', 10, 'F', '2018-03-01', NULL, 'SOCIAL', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'SOCIAL', NULL),
(62, '16204222-K', 'Angelica Carmen ', 'Cerda Mellado', 'Planta', 'Administrativo', 17, 'F', '2023-10-09', NULL, 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', NULL, NULL, NULL, 'OBRAS', NULL),
(63, '16396574-7', 'Ricardo Antonio ', 'Sobarzo Duran', 'Contrata', 'Auxiliar', 13, 'M', '2020-03-10', NULL, 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', NULL, NULL, NULL, 'TRANSITO', NULL),
(64, '16398926-3', 'Paulina Constanza ', 'Contreras Lopez', 'Planta', 'Profesional', 10, 'F', '2016-08-01', NULL, 'JUZGADO POLICIA LOCAL', 'JUZGADO POLICIA LOCAL', 'JUZGADO POLICIA LOCAL', NULL, NULL, NULL, 'JPL', NULL),
(65, '16512821-4', 'Ociel Hernan ', 'Rubilar Vallejos', 'Planta', 'Directivo', 8, 'M', '2023-08-22', NULL, 'CONTROL', 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', NULL, NULL, NULL, 'ALCALDIA', 'DIRECTOR'),
(66, '16651101-1', 'Marcos Antonio ', 'Salinas Parra', 'Planta', 'Directivo', 8, 'M', '2023-02-01', NULL, 'SECPLAN', 'SECPLAN', 'SECPLAN', NULL, NULL, NULL, NULL, 'DIRECTOR'),
(67, '16676045-3', 'Enoc Antonio', 'Solar San Martin', 'Contrata', 'Auxiliar', 16, 'M', '2020-01-21', NULL, 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', NULL, NULL, NULL, 'TRANSITO', NULL),
(68, '16676349-5', 'Isabel Antonia ', 'Venegas Matamala', 'Contrata', 'Administrativo', 15, 'F', '2023-02-01', NULL, 'DIREC. ADM. Y FINANZAS', 'SECPLAC', 'SECPLAC', NULL, NULL, NULL, 'DAF', NULL),
(69, '16987853-6', 'Pilar Carmen ', 'Almendras Oñate', 'Contrata', 'Administrativo', 16, 'F', '2018-01-01', NULL, 'JUZGADO POLICIA LOCAL', 'JUZGADO POLICIA LOCAL', 'JUZGADO POLICIA LOCAL', NULL, NULL, NULL, 'JPL', NULL),
(70, '16988024-7', 'Cristian Einner ', 'Guajardo Barrera', 'Contrata', 'Administrativo', 13, 'M', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'DIDECO', NULL),
(71, '16988111-1', 'Daniela Alejandra ', 'Melo Espiñeira', 'Contrata', 'Administrativo', 12, 'F', '2023-01-01', NULL, 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', 'DIRECCION DE OBRAS', NULL, NULL, NULL, 'OBRAS', NULL),
(72, '16988603-2', 'Igor Alberto ', 'Miranda Martinez', 'Contrata', 'Profesional', 10, 'M', '2015-02-02', NULL, 'SECRETARIA MUNICIPAL', 'Encargado de Informatica', 'Informatico Municipal, Publicador y Coordinador de Transparencia Activa, Encargado de Reloj Control, Encargado de Transformacion Digital ', 'informatica@municipalidadquilleco.cl', 'Ingeniero de Ejecución en Computación e Informática', '432633431', 'SECRETARIA', NULL),
(73, '17400193-6', 'Yomara Escarlet ', 'Carrillo Tapia', 'Contrata', 'Directivo', 8, 'F', '2021-07-05', '2024-12-06', 'SECPLAC', 'SECPLAC', 'SECPLAC', NULL, NULL, NULL, NULL, 'DIRECTOR'),
(74, '17538777-3', 'Gary Nixon ', 'Ibañez Reyes', 'Contrata', 'Profesional', 10, 'M', '2021-08-23', '2024-12-06', 'SECPLAC', 'SECPLAC', 'SECPLAC', NULL, NULL, NULL, NULL, NULL),
(75, '17744124-4', 'Carmen Rosa ', 'Mella Oliva', 'Contrata', 'Auxiliar', 18, 'F', '2023-01-01', NULL, 'TRANSITO', 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', NULL, NULL, NULL, 'TRANSITO', NULL),
(76, '18102560-3', 'Lissette Nicole ', 'Jofre Diaz', 'Contrata', 'Profesional', 10, 'F', '2020-05-01', NULL, 'SOCIAL', 'ENCARGADA DE DISCAPACIDAD', 'ENCARGADA DE DISCAPACIDAD', 'DIDECO@MUNICIPALIDADQUILLECO.CL', NULL, '432633400', 'SOCIAL', NULL),
(77, '18345038-7', 'Yasna ', 'Figueroa Contreras', 'Contrata', 'Administrativo', 13, 'F', '2025-03-01', NULL, 'SOCIAL', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'SOCIAL', NULL),
(78, '18345407-2', 'Silvia Alejandra ', 'Vergara Saldias', 'Contrata', 'Auxiliar', 18, 'F', '2023-01-01', NULL, 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', NULL, NULL, NULL, 'ADMIN', NULL),
(79, '18800907-7', 'Gissela Victoria ', 'Soto Godoy', 'Contrata', 'Administrativo', 13, 'F', '2023-01-01', NULL, 'SECPLAN', 'SECPLAC', 'SECPLAC', NULL, NULL, NULL, 'SECPLAN', NULL),
(80, '18804630-4', 'Neil Alejandro ', 'Manosalva Matamala', '', 'Auxiliar', 18, '', '2023-01-01', '2025-12-31', 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', 'neil@hotmail.com', 'TEC. ANALISTA PROGRAMADOR', '927463235', 'SECRETARIA', NULL),
(81, '19005012-2', 'Bastian Alfonso ', 'Ahumada Salamanca', 'Contrata', 'Administrativo', 13, 'M', '2023-01-01', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'DIDECO', NULL),
(82, '19372781-6', 'Felipe Eduardo ', 'Rebolledo Almendras', 'Contrata', 'Auxiliar', 16, 'M', '2023-01-01', NULL, 'SOCIAL', 'DIDECO', 'DIDECO', NULL, NULL, NULL, 'SOCIAL', NULL),
(83, '20686826-0', 'Javiera ', 'Salgado Hormazabal', 'Contrata', 'Auxiliar', 18, 'F', '2023-01-01', NULL, 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', 'SECRETARIA MUNICIPAL', NULL, NULL, NULL, 'SECRETARIA', NULL),
(84, '16650856-8', 'Alvaro Esteban', 'Rifo Contreras', 'Contrata', 'Auxiliar', 16, 'M', '2024-07-01', NULL, 'ADMINISTRACION MUNICIPAL', 'CHOFER DE MAQUINARIA', 'CHOFER DE MAQUINARIA', NULL, NULL, NULL, 'TRANSITO', NULL),
(85, '13142975-4', 'Justino Alamiro', 'Diaz Seguel', 'Codigo del trabajo', 'C.T.', 0, 'M', '2008-08-01', NULL, 'CEMENTERIO', 'ENCARGADO DEL CEMENTERIO MUNICIPAL, ESTAFETA', 'ENCARGADO DEL CEMENTERIO MUNICIPAL, ESTAFETA', NULL, NULL, NULL, '', NULL),
(86, '9821734-7', 'Hugo Patricio', 'Inostroza Ramirez', 'Planta', 'Directivo', 8, 'M', '2024-12-07', '2025-03-31', 'ADMINISTRACION MUNICIPAL', 'ADMINISTRADOR MUNICIPAL', 'ADMINISTRADOR MUNICIPAL', NULL, NULL, NULL, '', 'DIRECTOR'),
(87, '7488035-5', 'Claudio Edelberto', 'Solar Jara', 'Planta', 'Alcalde', 6, 'M', '2024-12-06', NULL, 'ALCALDE', 'ALCALDE', 'ALCALDE', NULL, NULL, NULL, '', 'ALCALDE'),
(88, '19545145-1', 'Jaime Andres', 'Stevens Reyes', 'Contrata', 'Profesional', 10, 'M', '2024-12-16', NULL, 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', 'ADMINISTRACION MUNICIPAL', NULL, NULL, NULL, 'ADMIN', NULL),
(89, '11602629-5', 'Mauricio Antonio', 'Sandoval Rivas', 'Planta', 'Directivo', 8, 'M', '2025-01-02', NULL, 'DIDECO', 'DIDECO', 'DIDECO', NULL, NULL, NULL, '', 'DIRECTOR'),
(90, '10119451-5', 'Gabriel Antonio', 'Diaz Gonzalez', 'Honorario', 'Honorario', 0, 'M', '2024-08-14', '2024-12-31', 'ADMINISTRACION MUNICIPAL', 'AUXILIAR', 'AUXILIAR', NULL, NULL, NULL, NULL, NULL),
(91, '12325503-8', 'Luis Arturo', 'Crisostomo Guerrero', 'Codigo del Trabajo', 'Codigo del Trabajo', 0, 'M', '2024-08-14', '2024-12-31', 'ADMINISTRACION MUNICIPAL', 'AUXILIAR', 'AUXILIAR', NULL, NULL, NULL, NULL, NULL),
(92, '12769157-6', 'Marcelo Hernan', 'Vasquez Sandoval', 'Codigo del Trabajo', 'C.T.', 0, 'M', '2025-02-01', '2025-12-31', 'CEMENTERIO', '0', 'ADMINISTRATIVO FINANZAS CEMENTERIO ', NULL, NULL, NULL, '', NULL),
(93, '15670707-4', 'Daisy', 'Sanhueza Fuentes', 'Planta', 'Directivo', 8, 'F', '2025-04-10', NULL, 'ADMINISTRACION MUNICIPAL', 'ADMINISTRADOR MUNICIPAL', 'ADMINISTRADOR', NULL, NULL, NULL, '', 'DIRECTOR'),
(94, '18592541-2', 'Tomas Alfonso', 'Reyes Godoy', 'Contrata', 'Profesional', 10, 'M', '2025-04-01', NULL, 'SECPLAN', 'SECPLAN', 'SECPLAN', NULL, NULL, NULL, 'SECPLAN', NULL),
(95, '17418207-8', 'Merav', 'Martinez Fernandez', 'Honorario', 'Honorario', 18, 'F', '2025-02-03', '2025-12-30', 'SECPLAN', '', '', NULL, NULL, NULL, '', NULL),
(96, '11417170-0', 'Cesar ', 'Gonzalez Troncoso', 'Planta', 'Directivo', 10, 'M', '2025-04-10', NULL, 'SEGURIDAD PUBLICA', 'director seguridad publica', '', NULL, NULL, NULL, NULL, 'DIRECTOR'),
(97, '15205441-6', 'Edgar ', 'Gonzalez Perez', 'Contrata', 'Administrativo', 13, 'M', '2025-04-10', NULL, 'SEGURIDAD PUBLICA', 'Inspector', '', NULL, NULL, NULL, NULL, NULL),
(98, '14107388-5', 'Margie', 'Gatica Gatica', 'Contrata', 'Profesional', 10, 'F', '2025-06-01', NULL, 'TRANSITO', 'Contruccion', '', NULL, NULL, NULL, NULL, NULL),
(99, '19599917-1', 'Tomas Ignacio', 'Morales Arratia', 'Honorarios', 'Honorario', 0, 'M', '2025-03-01', NULL, 'SECPLAN', 'Honorario', 'Honorario', NULL, NULL, NULL, '', NULL),
(100, '19372691-7', 'Yanissa Scarlette', 'Araneda Figueroa', 'Contrata', 'Administrativo', 12, 'F', '2025-04-01', NULL, 'OBRAS', 'ADMINISTRATIVA DE OBRAS', 'ADMINISTRATIVA DE OBRAS', NULL, NULL, NULL, 'OBRAS', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gestion_compra`
--

CREATE TABLE `gestion_compra` (
  `Id` int(11) NOT NULL,
  `Orden_Id` int(11) NOT NULL,
  `Usuario_Id` int(11) DEFAULT NULL,
  `Fecha_Gestion` datetime DEFAULT NULL,
  `Proveedor_Contactado` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_archivos`
--

CREATE TABLE `orden_archivos` (
  `Id` int(11) NOT NULL,
  `Orden_Id` int(11) NOT NULL,
  `Nombre_Archivo` varchar(255) NOT NULL,
  `Nombre_Original` varchar(255) NOT NULL,
  `Tipo_Documento` varchar(50) DEFAULT NULL,
  `Ruta_Archivo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_item`
--

CREATE TABLE `orden_item` (
  `Id` int(11) NOT NULL,
  `Orden_Id` int(11) NOT NULL,
  `Nombre_producto_servicio` varchar(255) DEFAULT NULL,
  `Codigo_Producto` varchar(100) DEFAULT NULL,
  `Cantidad` int(11) DEFAULT NULL,
  `Valor_Unitario` decimal(12,2) DEFAULT NULL,
  `Valor_Total` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_pedido`
--

CREATE TABLE `orden_pedido` (
  `Id` int(11) NOT NULL,
  `Solicitante_Id` int(11) DEFAULT NULL,
  `Nombre_Orden` varchar(255) DEFAULT NULL,
  `Fecha_Creacion` datetime DEFAULT current_timestamp(),
  `Tipo_Compra` varchar(100) DEFAULT NULL,
  `Presupuesto` varchar(100) DEFAULT NULL,
  `Subprog` varchar(100) DEFAULT NULL,
  `Centro_Costos` varchar(100) DEFAULT NULL,
  `Valor_neto` decimal(12,2) DEFAULT NULL,
  `Plazo_maximo` varchar(100) DEFAULT NULL,
  `Iva` decimal(10,2) DEFAULT NULL,
  `Valor_total` decimal(12,2) DEFAULT NULL,
  `Estado` varchar(50) DEFAULT NULL,
  `Motivo_Rechazo` text DEFAULT NULL,
  `Motivo_Compra` text DEFAULT NULL,
  `Cuenta_Presupuestaria` varchar(100) DEFAULT NULL,
  `Id_Licitacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `firmas_orden`
--
ALTER TABLE `firmas_orden`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Usuario_Id` (`Usuario_Id`),
  ADD KEY `Orden_Id` (`Orden_Id`);

--
-- Indices de la tabla `funcionarios_muni`
--
ALTER TABLE `funcionarios_muni`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_rut` (`RUT`);

--
-- Indices de la tabla `gestion_compra`
--
ALTER TABLE `gestion_compra`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Orden_Id` (`Orden_Id`),
  ADD KEY `fk_gestion_usuario` (`Usuario_Id`);

--
-- Indices de la tabla `orden_archivos`
--
ALTER TABLE `orden_archivos`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Orden_Id` (`Orden_Id`);

--
-- Indices de la tabla `orden_item`
--
ALTER TABLE `orden_item`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Orden_Id` (`Orden_Id`);

--
-- Indices de la tabla `orden_pedido`
--
ALTER TABLE `orden_pedido`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_solicitante` (`Solicitante_Id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `firmas_orden`
--
ALTER TABLE `firmas_orden`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `funcionarios_muni`
--
ALTER TABLE `funcionarios_muni`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT de la tabla `gestion_compra`
--
ALTER TABLE `gestion_compra`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orden_archivos`
--
ALTER TABLE `orden_archivos`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orden_item`
--
ALTER TABLE `orden_item`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orden_pedido`
--
ALTER TABLE `orden_pedido`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `firmas_orden`
--
ALTER TABLE `firmas_orden`
  ADD CONSTRAINT `firmas_orden_ibfk_1` FOREIGN KEY (`Usuario_Id`) REFERENCES `funcionarios_muni` (`ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `firmas_orden_ibfk_2` FOREIGN KEY (`Orden_Id`) REFERENCES `orden_pedido` (`Id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `gestion_compra`
--
ALTER TABLE `gestion_compra`
  ADD CONSTRAINT `fk_gestion_usuario` FOREIGN KEY (`Usuario_Id`) REFERENCES `funcionarios_muni` (`ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `gestion_compra_ibfk_1` FOREIGN KEY (`Orden_Id`) REFERENCES `orden_pedido` (`Id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orden_archivos`
--
ALTER TABLE `orden_archivos`
  ADD CONSTRAINT `orden_archivos_ibfk_1` FOREIGN KEY (`Orden_Id`) REFERENCES `orden_pedido` (`Id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orden_item`
--
ALTER TABLE `orden_item`
  ADD CONSTRAINT `orden_item_ibfk_1` FOREIGN KEY (`Orden_Id`) REFERENCES `orden_pedido` (`Id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orden_pedido`
--
ALTER TABLE `orden_pedido`
  ADD CONSTRAINT `fk_orden_funcionario` FOREIGN KEY (`Solicitante_Id`) REFERENCES `funcionarios_muni` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;