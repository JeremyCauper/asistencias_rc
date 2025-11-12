/*
 Navicat Premium Data Transfer

 Source Server         : local_mysql
 Source Server Type    : MySQL
 Source Server Version : 100432
 Source Host           : localhost:3306
 Source Schema         : asistencias

 Target Server Type    : MySQL
 Target Server Version : 100432
 File Encoding         : 65001

 Date: 31/10/2025 16:32:03
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for asistencias
-- ----------------------------
DROP TABLE IF EXISTS `asistencias`;
CREATE TABLE `asistencias`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time(0) NULL DEFAULT NULL,
  `tipo_modalidad` tinyint(1) NOT NULL DEFAULT 1,
  `tipo_asistencia` tinyint(1) NOT NULL DEFAULT 0,
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `sincronizado` tinyint(4) NULL DEFAULT 1,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_asistencia_user_fecha`(`user_id`, `fecha`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17889 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for config_trabajo_personal
-- ----------------------------
DROP TABLE IF EXISTS `config_trabajo_personal`;
CREATE TABLE `config_trabajo_personal`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lunes` tinyint(1) NOT NULL DEFAULT 1,
  `martes` tinyint(1) NOT NULL DEFAULT 1,
  `miercoles` tinyint(1) NOT NULL DEFAULT 1,
  `jueves` tinyint(1) NOT NULL DEFAULT 1,
  `viernes` tinyint(1) NOT NULL DEFAULT 1,
  `sabado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_user_id`(`user_id`) USING BTREE,
  CONSTRAINT `fk_config_trabajo_personal_user` FOREIGN KEY (`user_id`) REFERENCES `personal` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 64 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for descuentos_asistencia
-- ----------------------------
DROP TABLE IF EXISTS `descuentos_asistencia`;
CREATE TABLE `descuentos_asistencia`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `monto_descuento` decimal(10, 2) NOT NULL DEFAULT 5,
  `comentario` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8211 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for empresa
-- ----------------------------
DROP TABLE IF EXISTS `empresa`;
CREATE TABLE `empresa`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ruc` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `razon_social` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1,
  `eliminado` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `ruc_unique`(`ruc`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for feriados_privado_peru
-- ----------------------------
DROP TABLE IF EXISTS `feriados_privado_peru`;
CREATE TABLE `feriados_privado_peru`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mes` int(11) NOT NULL,
  `dia` int(11) NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for personal
-- ----------------------------
DROP TABLE IF EXISTS `personal`;
CREATE TABLE `personal`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `empresa_ruc` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dni` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `apellido` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `role` tinyint(1) NULL DEFAULT 0,
  `password` int(11) NULL DEFAULT NULL,
  `cardno` int(11) NULL DEFAULT NULL,
  `rol_system` tinyint(1) NULL DEFAULT 0,
  `password_system` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `estado_sync` tinyint(4) NULL DEFAULT 0,
  `estatus` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0) ON UPDATE CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_userid`(`user_id`) USING BTREE,
  UNIQUE INDEX `idx_dni`(`dni`) USING BTREE,
  INDEX `empresa_ruc`(`empresa_ruc`) USING BTREE,
  CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`empresa_ruc`) REFERENCES `empresa` (`ruc`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 53 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
