-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: setup-mysql
-- Tempo de geração: 20-Ago-2025 às 23:52
-- Versão do servidor: 8.0.42
-- versão do PHP: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de dados: `laravel`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `bookers`
--

CREATE TABLE `bookers` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_commission_rate` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `bookers`
--

INSERT INTO `bookers` (`id`, `name`, `default_commission_rate`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'SCOOB', 5.00, '2025-06-18 21:34:39', '2025-06-18 21:34:39', NULL),
(2, 'KLAH', 5.00, '2025-06-18 21:34:39', '2025-06-18 21:34:39', NULL),
(3, 'BONET', 5.00, '2025-06-18 21:34:39', '2025-06-18 21:34:39', NULL),
(4, 'JORDO', 5.00, '2025-06-18 21:34:39', '2025-06-18 21:34:39', NULL),
(5, 'VICTOR HUGO', 5.00, '2025-06-18 21:34:39', '2025-06-18 21:34:39', NULL),
(6, 'PEDRO', 3.00, '2025-06-18 21:34:39', '2025-07-24 18:56:50', NULL),
(7, 'JOTTA', 5.00, '2025-06-18 21:34:39', '2025-06-18 21:34:39', NULL),
(8, 'KLAH/JORDO', 5.00, '2025-06-18 21:34:39', '2025-06-18 21:34:39', NULL),
(9, 'GUI', 5.00, '2025-06-18 21:34:39', '2025-06-18 21:34:39', NULL),
(10, 'GUI / JORDO', 5.00, '2025-07-01 18:35:26', '2025-07-01 18:35:41', NULL),
(11, 'CORAL', 0.00, '2025-07-03 15:34:30', '2025-07-24 19:16:53', NULL),
(16, 'VICTOR HUGO / JORDO', 5.00, '2025-07-22 17:29:22', '2025-07-22 17:29:22', NULL),
(17, 'PEDRO / JORDO', 5.00, '2025-07-24 17:39:11', '2025-07-24 17:39:11', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `bookers`
--
ALTER TABLE `bookers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bookers_name_unique` (`name`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `bookers`
--
ALTER TABLE `bookers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
