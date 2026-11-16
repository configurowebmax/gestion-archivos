-- Base de datos: `gestion-archivos`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Estructura de tabla para la tabla `share_links`

CREATE TABLE `share_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `token` varchar(32) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `share_links_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `tbl_file` (`tbl_file_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `tbl_file`

CREATE TABLE `tbl_file` (
  `tbl_file_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_title` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `file_uploader` varchar(255) NOT NULL,
  `date_uploaded` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`tbl_file_id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `tbl_users`

CREATE TABLE `tbl_users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL DEFAULT 'user',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `tbl_users` (`user_id`, `username`, `password`, `role`) VALUES
('1', 'configuroweb', '$2y$10$yUsY5L1MhYLry.uXNNbnkuurBPgkDKIRLRRUgYBVy/tp91UgDYsYy', 'admin'),
('2', 'jusuario', '$2y$10$KUI7J9JW85Bd8u/c5g5bT.lreyu6xPanHqQ9ozdFOyjk3sOHUHlFC', 'user'),
('8', 'jadministrador', '$2y$10$qnAeEHDXShZ62EKYhYR5Buer9W32nCcouEcSwOFnhDQdRVevsOl1m', 'admin');

COMMIT;
