-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: mysql-8.4
-- Время создания: Дек 18 2025 г., 21:55
-- Версия сервера: 8.4.6
-- Версия PHP: 8.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `time`
--

-- --------------------------------------------------------

--
-- Структура таблицы `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','director','senior_nurse','employee') NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `gender` enum('male','female') DEFAULT NULL,
  `position_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `employees`
--

INSERT INTO `employees` (`id`, `full_name`, `phone`, `password_hash`, `role`, `department`, `hire_date`, `created_at`, `updated_at`, `gender`, `position_code`) VALUES
(2, 'Администратор', '79991234567', '$2y$12$lHUMcoOpHAuB0jb2vYeiK.Ep51Pegv/tGXJ9bTGY74FBbsPVTBy8K', 'admin', 'IT', '2024-01-01', '2025-11-10 05:37:14', '2025-11-12 05:28:11', 'male', NULL),
(159, 'Севастьянов Роман Игоревич', '79028017063', '$2y$12$a4tsRx5Av2mKPuAuixr1M.sTM8YFwGYAfHIJYGIaVXyN1gamflQ1W', 'employee', 'floor_1', NULL, '2025-11-16 06:42:48', '2025-11-16 06:42:48', 'male', 'sanitar'),
(160, 'Сотников Фёдор Лукич', '79025517330', '$2y$12$PyZtiug38zu.re7s2bD8lesKPm7juZLpkL8DgG7lAtOM4vMkUy0B6', 'employee', 'floor_1', NULL, '2025-11-16 06:42:48', '2025-11-16 06:42:48', 'male', 'sanitar'),
(161, 'Фролов Марк Лукич', '79028078391', '$2y$12$SBlZYvaXEgPOtbmYV..NPOr9U3/1chfn1D2.DwusJlRavQnEAmS56', 'employee', 'floor_1', NULL, '2025-11-16 06:42:48', '2025-11-16 06:42:48', 'male', 'sanitar'),
(162, 'Селиванов Александр Демидович', '79029130782', '$2y$12$RgOF3j.JRYzOkFpx8WDAr.V.lr9C16vIQnNTeURmwrxJ/QKN8X1Jy', 'employee', 'floor_1', NULL, '2025-11-16 06:42:49', '2025-11-16 06:42:49', 'male', 'sanitar'),
(163, 'Карасев Иван Егорович', '79028438171', '$2y$12$Yjjjnt52hKa1e2rzn6LKi.A8Tc9SPqZNxlCTVkuef5ZlofvJv4bCS', 'employee', 'floor_1', NULL, '2025-11-16 06:42:49', '2025-11-16 06:42:49', 'male', 'sanitar'),
(164, 'Корчагин Михаил Александрович', '79023346757', '$2y$12$4q6QW39Xp7Zn.qiR.x6jpeQH2ouOzkuaCSXP7yXKcmfBNV9UXJhOy', 'employee', 'floor_1', NULL, '2025-11-16 06:42:49', '2025-11-16 06:42:49', 'male', 'sanitar'),
(165, 'Ильин Андрей Саввич', '79025449457', '$2y$12$H1MrDN7FQncC3zaEXP/UGuzOXazWnPJZ7N0QQMejCxWTKQkMs3Sx6', 'employee', 'floor_1', NULL, '2025-11-16 06:42:49', '2025-11-16 06:42:49', 'male', 'sanitar'),
(166, 'Николаев Иван Сергеевич', '79025144837', '$2y$12$JvEVykdBYjnMssEes5lBv.4TFnAEa8KP.CCVylLjK8EIdKqL4PCw2', 'employee', 'floor_1', '2020-07-04', '2025-11-16 06:42:50', '2025-11-20 18:44:58', 'male', 'sanitar'),
(167, 'Сергеев Егор Маркович', '79020372198', '$2y$12$vSLzBfGJUjbwS.a/6L6gzehc4cDfZP4YXC8OGITcfi2Go.jy7Gayi', 'employee', 'floor_1', NULL, '2025-11-16 06:42:50', '2025-11-16 06:42:50', 'male', 'sanitar'),
(168, 'Скворцов Андрей Николаевич', '79023300985', '$2y$12$SRuetc.1NIL9sul26crmX.AxaPD76i3KA/zgZ4NqV44pF1ixNrP4K', 'employee', 'floor_1', NULL, '2025-11-16 06:42:50', '2025-11-16 06:42:50', 'male', 'sanitar'),
(169, 'Кузнецов Егор Владиславович', '79022946510', '$2y$12$Qrccz5veERGMEHCxz6PjFuACJzswnLkGItCxhZd/qhtD7hOh1tRoK', 'employee', 'floor_2', NULL, '2025-11-16 06:42:51', '2025-11-16 06:42:51', 'male', 'sanitar'),
(170, 'Афанасьев Мирослав Эминович', '79023473182', '$2y$12$5RfYwBwTc.vPj4XtrOlOMupWobImGHihXtaosO1BVLdDwStagbRXq', 'employee', 'floor_2', NULL, '2025-11-16 06:42:51', '2025-11-16 06:42:51', 'male', 'sanitar'),
(171, 'Быков Вадим Максимович', '79022943937', '$2y$12$3bMx6Ld3Aj0higZYUBAKreDI3l9YP.XiMGRNORIsFtdBlqf5q.Wo.', 'employee', 'floor_2', NULL, '2025-11-16 06:42:51', '2025-11-16 06:42:51', 'male', 'sanitar'),
(172, 'Попов Богдан Андреевич', '79027826680', '$2y$12$q0ZOYSuBhZVKcFHXlJK1kOHmdTe8mluFVgK.SgPeoIgN6TzsGm7gm', 'employee', 'floor_2', NULL, '2025-11-16 06:42:51', '2025-11-16 06:42:51', 'male', 'sanitar'),
(173, 'Власов Владислав Максимович', '79028453656', '$2y$12$BSELj2V/rxxxUX7UAciGTu.nFld/L/B3naYwqsD2FDtW9zxjQkiKK', 'employee', 'floor_2', NULL, '2025-11-16 06:42:51', '2025-11-16 06:42:51', 'male', 'sanitar'),
(174, 'Потапов Артём Олегович', '79020432254', '$2y$12$AU6ox5/ouyfA.g.K80kSDe3T1sEwW3yRaxdYncE6C5Q0OQn/97v5.', 'employee', 'Не указан', NULL, '2025-11-16 06:42:52', '2025-11-16 06:42:52', 'male', 'assistant'),
(175, 'Савин Даниил Ярославович', '79025874147', '$2y$12$4gX1xUSTa5KT38zqW833sO860gXL68TVxSegsjm0sEVhlWRtaymoO', 'employee', 'Не указан', NULL, '2025-11-16 06:42:52', '2025-11-16 06:42:52', 'male', 'assistant'),
(176, 'Воронина Анна Ивановна', '79026396164', '$2y$12$5.wS4D5myaj4c558D6yVKeyUeru5.JjMfd4BfL5ON34aw4T0YlGT6', 'senior_nurse', '', '2025-11-21', '2025-11-20 17:04:43', '2025-11-21 11:46:28', 'female', 'senior_nurse'),
(177, 'Крылова Любовь Артёмовна', '79021830111', '$2y$12$UNWyZD7j5rb0jeiQ7D56huLKEqISEHQkygEfjLtCRF4vlcdNGgI72', 'director', '', '2025-11-21', '2025-11-20 17:05:07', '2025-11-21 11:47:14', 'female', 'director');

-- --------------------------------------------------------

--
-- Структура таблицы `payroll`
--

CREATE TABLE `payroll` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `year` int NOT NULL,
  `month` int NOT NULL,
  `base_salary` decimal(10,2) NOT NULL,
  `hours_worked` decimal(5,1) NOT NULL,
  `norm_hours` decimal(5,1) NOT NULL,
  `night_hours` decimal(5,1) NOT NULL DEFAULT '0.0',
  `harmful_bonus` decimal(10,2) NOT NULL DEFAULT '0.00',
  `experience_bonus` decimal(10,2) NOT NULL DEFAULT '0.00',
  `special_work_bonus` decimal(10,2) NOT NULL DEFAULT '0.00',
  `night_bonus` decimal(10,2) NOT NULL DEFAULT '0.00',
  `rayon_coeff_sum` decimal(10,2) NOT NULL DEFAULT '0.00',
  `north_bonus_sum` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_pay` decimal(10,2) NOT NULL,
  `calculated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `positions`
--

CREATE TABLE `positions` (
  `code` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `positions`
--

INSERT INTO `positions` (`code`, `title`, `salary`, `created_at`, `updated_at`) VALUES
('assistant', 'Ассистент', 12500.00, '2025-11-15 15:22:04', '2025-11-17 04:15:55'),
('director', 'Директор', 13000.00, '2025-11-15 15:22:04', '2025-11-17 04:15:55'),
('nurse', 'Медсестра', 13000.00, '2025-11-15 15:22:04', '2025-11-17 04:15:55'),
('sanitar', 'Санитар', 13000.00, '2025-11-15 15:22:04', '2025-11-17 04:15:55'),
('sanitarka', 'Санитарка', 13000.00, '2025-11-15 15:22:04', '2025-11-17 04:15:55'),
('senior_nurse', 'Старшая медсестра', 13000.00, '2025-11-15 15:22:04', '2025-11-17 04:15:55'),
('sidelka', 'Сиделка', 13000.00, '2025-11-15 15:22:04', '2025-11-17 04:15:55'),
('vanshiza', 'Ванщица', 13000.00, '2025-11-15 15:22:04', '2025-11-17 04:15:55');

-- --------------------------------------------------------

--
-- Структура таблицы `schedule`
--

CREATE TABLE `schedule` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `date` date NOT NULL,
  `shift_type` varchar(10) DEFAULT 'off',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `schedule`
--

INSERT INTO `schedule` (`id`, `employee_id`, `date`, `shift_type`, `created_at`) VALUES
(4540, 165, '2025-11-01', '10ч', '2025-11-23 22:22:09'),
(4541, 163, '2025-11-02', '10ч', '2025-11-23 22:22:09'),
(4542, 163, '2025-11-03', '14ч', '2025-11-23 22:22:09'),
(4543, 163, '2025-11-08', '14ч', '2025-11-23 22:22:09'),
(4544, 163, '2025-11-07', '10ч', '2025-11-23 22:22:09'),
(4545, 163, '2025-11-12', '10ч', '2025-11-23 22:22:09'),
(4546, 163, '2025-11-13', '14ч', '2025-11-23 22:22:09'),
(4547, 163, '2025-11-17', '10ч', '2025-11-23 22:22:09'),
(4548, 163, '2025-11-18', '14ч', '2025-11-23 22:22:09'),
(4549, 163, '2025-11-22', '10ч', '2025-11-23 22:22:09'),
(4550, 163, '2025-11-23', '14ч', '2025-11-23 22:22:09'),
(4551, 163, '2025-11-27', '10ч', '2025-11-23 22:22:09'),
(4552, 163, '2025-11-28', '14ч', '2025-11-23 22:22:09'),
(4553, 164, '2025-11-03', '10ч', '2025-11-23 22:22:09'),
(4554, 164, '2025-11-04', '14ч', '2025-11-23 22:22:09'),
(4555, 164, '2025-11-08', '10ч', '2025-11-23 22:22:09'),
(4556, 166, '2025-11-04', '10ч', '2025-11-23 22:22:10'),
(4557, 166, '2025-11-10', '14ч', '2025-11-23 22:22:10'),
(4558, 166, '2025-11-05', '14ч', '2025-11-23 22:22:10'),
(4559, 166, '2025-11-09', '10ч', '2025-11-23 22:22:10'),
(4560, 166, '2025-11-14', '10ч', '2025-11-23 22:22:10'),
(4561, 166, '2025-11-15', '14ч', '2025-11-23 22:22:10'),
(4562, 166, '2025-11-19', '10ч', '2025-11-23 22:22:10'),
(4563, 166, '2025-11-20', '14ч', '2025-11-23 22:22:10'),
(4564, 166, '2025-11-24', '10ч', '2025-11-23 22:22:10'),
(4565, 166, '2025-11-25', '14ч', '2025-11-23 22:22:10'),
(4566, 166, '2025-11-29', '10ч', '2025-11-23 22:22:10'),
(4567, 166, '2025-11-30', '14ч', '2025-11-23 22:22:10'),
(4568, 159, '2025-11-05', '10ч', '2025-11-23 22:22:10'),
(4569, 159, '2025-11-06', '14ч', '2025-11-23 22:22:10'),
(4570, 159, '2025-11-15', '10ч', '2025-11-23 22:22:10'),
(4571, 159, '2025-11-11', '14ч', '2025-11-23 22:22:10'),
(4572, 159, '2025-11-10', '10ч', '2025-11-23 22:22:10'),
(4573, 159, '2025-11-16', '14ч', '2025-11-23 22:22:10'),
(4574, 159, '2025-11-20', '10ч', '2025-11-23 22:22:10'),
(4575, 159, '2025-11-21', '14ч', '2025-11-23 22:22:10'),
(4576, 159, '2025-11-25', '10ч', '2025-11-23 22:22:10'),
(4577, 159, '2025-11-26', '14ч', '2025-11-23 22:22:10'),
(4578, 159, '2025-11-30', '10ч', '2025-11-23 22:22:10'),
(4579, 159, '2025-11-01', '14ч', '2025-11-23 22:22:10'),
(4580, 162, '2025-11-06', '10ч', '2025-11-23 22:22:11'),
(4581, 162, '2025-11-07', '14ч', '2025-11-23 22:22:11'),
(4582, 162, '2025-11-11', '10ч', '2025-11-23 22:22:11'),
(4583, 162, '2025-11-12', '14ч', '2025-11-23 22:22:11'),
(4584, 162, '2025-11-16', '10ч', '2025-11-23 22:22:11'),
(4585, 162, '2025-11-17', '14ч', '2025-11-23 22:22:11'),
(4586, 162, '2025-11-21', '10ч', '2025-11-23 22:22:11'),
(4587, 162, '2025-11-22', '14ч', '2025-11-23 22:22:11'),
(4588, 162, '2025-11-26', '10ч', '2025-11-23 22:22:11'),
(4589, 162, '2025-11-27', '14ч', '2025-11-23 22:22:11'),
(4590, 162, '2025-11-02', '14ч', '2025-11-23 22:22:11'),
(4591, 162, '2025-11-01', '10ч', '2025-11-23 22:22:11'),
(4592, 167, '2025-11-07', '10ч', '2025-11-23 22:22:11'),
(4593, 167, '2025-11-08', '14ч', '2025-11-23 22:22:11'),
(4594, 167, '2025-11-13', '14ч', '2025-11-23 22:22:11'),
(4595, 167, '2025-11-12', '10ч', '2025-11-23 22:22:11'),
(4596, 167, '2025-11-17', '10ч', '2025-11-23 22:22:11'),
(4597, 167, '2025-11-18', '14ч', '2025-11-23 22:22:11'),
(4598, 167, '2025-11-22', '10ч', '2025-11-23 22:22:11'),
(4599, 167, '2025-11-23', '14ч', '2025-11-23 22:22:11'),
(4600, 167, '2025-11-27', '10ч', '2025-11-23 22:22:11'),
(4601, 167, '2025-11-28', '14ч', '2025-11-23 22:22:11'),
(4602, 167, '2025-11-03', '14ч', '2025-11-23 22:22:11'),
(4603, 167, '2025-11-02', '10ч', '2025-11-23 22:22:11'),
(4604, 168, '2025-11-18', '10ч', '2025-11-23 22:22:12'),
(4605, 168, '2025-11-19', '14ч', '2025-11-23 22:22:12'),
(4606, 168, '2025-11-23', '10ч', '2025-11-23 22:22:12'),
(4607, 168, '2025-11-24', '14ч', '2025-11-23 22:22:12'),
(4608, 168, '2025-11-28', '10ч', '2025-11-23 22:22:12'),
(4609, 168, '2025-11-29', '14ч', '2025-11-23 22:22:12'),
(4610, 160, '2025-11-30', '14ч', '2025-11-23 22:22:13'),
(4611, 161, '2025-11-01', '14ч', '2025-11-23 22:22:13'),
(4612, 170, '2025-11-11', '10ч', '2025-11-23 22:22:14'),
(4613, 170, '2025-11-12', '14ч', '2025-11-23 22:22:14'),
(4614, 170, '2025-11-17', '14ч', '2025-11-23 22:22:14'),
(4615, 170, '2025-11-16', '10ч', '2025-11-23 22:22:14'),
(4616, 170, '2025-11-21', '10ч', '2025-11-23 22:22:14'),
(4617, 170, '2025-11-22', '14ч', '2025-11-23 22:22:14'),
(4618, 170, '2025-11-26', '10ч', '2025-11-23 22:22:14'),
(4619, 170, '2025-11-27', '14ч', '2025-11-23 22:22:14'),
(4620, 170, '2025-11-07', '14ч', '2025-11-23 22:22:14'),
(4621, 170, '2025-11-06', '10ч', '2025-11-23 22:22:14'),
(4622, 170, '2025-11-02', '14ч', '2025-11-23 22:22:14'),
(4623, 170, '2025-11-01', '10ч', '2025-11-23 22:22:14'),
(4624, 171, '2025-11-22', '10ч', '2025-11-23 22:22:15'),
(4625, 171, '2025-11-23', '14ч', '2025-11-23 22:22:15'),
(4626, 171, '2025-11-27', '10ч', '2025-11-23 22:22:15'),
(4627, 171, '2025-11-28', '14ч', '2025-11-23 22:22:15'),
(4628, 173, '2025-11-13', '10ч', '2025-11-23 22:22:16'),
(4629, 173, '2025-11-14', '14ч', '2025-11-23 22:22:16'),
(4630, 173, '2025-11-18', '10ч', '2025-11-23 22:22:16'),
(4631, 173, '2025-11-23', '10ч', '2025-11-23 22:22:16'),
(4632, 173, '2025-11-24', '14ч', '2025-11-23 22:22:16'),
(4633, 173, '2025-11-19', '14ч', '2025-11-23 22:22:16'),
(4634, 173, '2025-11-28', '10ч', '2025-11-23 22:22:16'),
(4635, 173, '2025-11-29', '14ч', '2025-11-23 22:22:16'),
(4636, 173, '2025-11-09', '14ч', '2025-11-23 22:22:16'),
(4637, 173, '2025-11-08', '10ч', '2025-11-23 22:22:16'),
(4638, 173, '2025-11-04', '14ч', '2025-11-23 22:22:16'),
(4639, 173, '2025-11-03', '10ч', '2025-11-23 22:22:16'),
(4640, 169, '2025-11-14', '10ч', '2025-11-23 22:22:17'),
(4641, 169, '2025-11-15', '14ч', '2025-11-23 22:22:17'),
(4642, 169, '2025-11-20', '14ч', '2025-11-23 22:22:17'),
(4643, 169, '2025-11-24', '10ч', '2025-11-23 22:22:17'),
(4644, 169, '2025-11-19', '10ч', '2025-11-23 22:22:17'),
(4645, 169, '2025-11-25', '14ч', '2025-11-23 22:22:17'),
(4646, 169, '2025-11-29', '10ч', '2025-11-23 22:22:17'),
(4647, 169, '2025-11-30', '14ч', '2025-11-23 22:22:17'),
(4648, 169, '2025-11-10', '14ч', '2025-11-23 22:22:17'),
(4649, 169, '2025-11-09', '10ч', '2025-11-23 22:22:17'),
(4650, 169, '2025-11-05', '14ч', '2025-11-23 22:22:17'),
(4651, 169, '2025-11-04', '10ч', '2025-11-23 22:22:17'),
(4652, 172, '2025-11-15', '10ч', '2025-11-23 22:22:17'),
(4653, 172, '2025-11-11', '14ч', '2025-11-23 22:22:17'),
(4654, 172, '2025-11-10', '10ч', '2025-11-23 22:22:17'),
(4655, 172, '2025-11-06', '14ч', '2025-11-23 22:22:17'),
(4656, 172, '2025-11-05', '10ч', '2025-11-23 22:22:17'),
(4657, 172, '2025-11-01', '14ч', '2025-11-23 22:22:17'),
(4658, 174, '2025-11-01', '10ч', '2025-11-23 22:22:21'),
(4659, 174, '2025-11-05', '10ч', '2025-11-23 22:22:21'),
(4660, 174, '2025-11-06', '10ч', '2025-11-23 22:22:21'),
(4661, 174, '2025-11-09', '10ч', '2025-11-23 22:22:21'),
(4662, 174, '2025-11-02', '10ч', '2025-11-23 22:22:21'),
(4663, 174, '2025-11-10', '10ч', '2025-11-23 22:22:21'),
(4664, 174, '2025-11-13', '10ч', '2025-11-23 22:22:21'),
(4665, 174, '2025-11-14', '10ч', '2025-11-23 22:22:21'),
(4666, 174, '2025-11-17', '10ч', '2025-11-23 22:22:21'),
(4667, 174, '2025-11-18', '10ч', '2025-11-23 22:22:21'),
(4668, 174, '2025-11-21', '10ч', '2025-11-23 22:22:22'),
(4669, 174, '2025-11-22', '10ч', '2025-11-23 22:22:22'),
(4670, 174, '2025-11-25', '10ч', '2025-11-23 22:22:22'),
(4671, 174, '2025-11-26', '10ч', '2025-11-23 22:22:22'),
(4672, 174, '2025-11-29', '10ч', '2025-11-23 22:22:22'),
(4673, 174, '2025-11-30', '10ч', '2025-11-23 22:22:22'),
(4674, 175, '2025-11-03', '10ч', '2025-11-23 22:22:22'),
(4675, 175, '2025-11-04', '10ч', '2025-11-23 22:22:22'),
(4676, 175, '2025-11-07', '10ч', '2025-11-23 22:22:22'),
(4677, 175, '2025-11-08', '10ч', '2025-11-23 22:22:22'),
(4678, 175, '2025-11-11', '10ч', '2025-11-23 22:22:22'),
(4679, 175, '2025-11-12', '10ч', '2025-11-23 22:22:22'),
(4680, 175, '2025-11-15', '10ч', '2025-11-23 22:22:22'),
(4681, 175, '2025-11-16', '10ч', '2025-11-23 22:22:22'),
(4682, 175, '2025-11-19', '10ч', '2025-11-23 22:22:22'),
(4683, 175, '2025-11-20', '10ч', '2025-11-23 22:22:22'),
(4684, 175, '2025-11-23', '10ч', '2025-11-23 22:22:22'),
(4685, 175, '2025-11-24', '10ч', '2025-11-23 22:22:22'),
(4686, 175, '2025-11-27', '10ч', '2025-11-23 22:22:22'),
(4687, 175, '2025-11-28', '10ч', '2025-11-23 22:22:22'),
(4688, 163, '2025-11-01', '', '2025-11-24 13:04:08');

-- --------------------------------------------------------

--
-- Структура таблицы `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `key_name` varchar(50) NOT NULL,
  `value` json NOT NULL,
  `description` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`, `description`, `updated_at`) VALUES
(3, 'work_norm_2025_1_m', '136', 'Норма мужчин, 1/2025', '2025-11-13 19:02:08'),
(4, 'work_norm_2025_1_f', '122.4', 'Норма женщин, 1/2025', '2025-11-13 19:03:26'),
(5, 'work_norm_2025_2_m', '160', 'Норма мужчин, 2/2025', '2025-11-13 19:03:26'),
(6, 'work_norm_2025_2_f', '144', 'Норма женщин, 2/2025', '2025-11-13 19:03:26'),
(7, 'work_norm_2025_3_m', '167', 'Норма мужчин, 3/2025', '2025-11-13 19:03:26'),
(8, 'work_norm_2025_3_f', '150.2', 'Норма женщин, 3/2025', '2025-11-13 19:03:26'),
(9, 'work_norm_2025_4_m', '175', 'Норма мужчин, 4/2025', '2025-11-13 19:03:26'),
(10, 'work_norm_2025_4_f', '157.4', 'Норма женщин, 4/2025', '2025-11-13 19:03:26'),
(11, 'work_norm_2025_5_m', '144', 'Норма мужчин, 5/2025', '2025-11-13 19:03:26'),
(12, 'work_norm_2025_5_f', '129.6', 'Норма женщин, 5/2025', '2025-11-13 19:03:26'),
(13, 'work_norm_2025_6_m', '151', 'Норма мужчин, 6/2025', '2025-11-13 19:03:26'),
(14, 'work_norm_2025_6_f', '135.8', 'Норма женщин, 6/2025', '2025-11-13 19:03:26'),
(15, 'work_norm_2025_7_m', '184', 'Норма мужчин, 7/2025', '2025-11-13 19:03:26'),
(16, 'work_norm_2025_7_f', '165.6', 'Норма женщин, 7/2025', '2025-11-13 19:03:26'),
(17, 'work_norm_2025_8_m', '168', 'Норма мужчин, 8/2025', '2025-11-13 19:03:26'),
(18, 'work_norm_2025_8_f', '151.2', 'Норма женщин, 8/2025', '2025-11-13 19:03:26'),
(19, 'work_norm_2025_9_m', '176', 'Норма мужчин, 9/2025', '2025-11-13 19:03:26'),
(20, 'work_norm_2025_9_f', '158.4', 'Норма женщин, 9/2025', '2025-11-13 19:03:26'),
(21, 'work_norm_2025_10_m', '184', 'Норма мужчин, 10/2025', '2025-11-13 19:03:26'),
(22, 'work_norm_2025_10_f', '165.6', 'Норма женщин, 10/2025', '2025-11-13 19:03:26'),
(23, 'work_norm_2025_11_m', '151', 'Норма мужчин, 11/2025', '2025-11-13 19:03:26'),
(24, 'work_norm_2025_11_f', '135.8', 'Норма женщин, 11/2025', '2025-11-13 19:03:26'),
(25, 'work_norm_2025_12_m', '176', 'Норма мужчин, 12/2025', '2025-11-13 19:03:26'),
(26, 'work_norm_2025_12_f', '158.4', 'Норма женщин, 12/2025', '2025-11-13 19:03:26'),
(147, 'bonus_harmful', '0.05', 'Доплата: bonus_harmful', '2025-11-17 18:27:53'),
(148, 'bonus_experience', '0.2', 'Доплата: bonus_experience', '2025-11-17 18:27:53'),
(149, 'bonus_special_work', '0.06', 'Доплата: bonus_special_work', '2025-11-17 18:27:53'),
(150, 'bonus_rayon', '1', 'Доплата: bonus_rayon', '2025-11-17 18:27:53'),
(151, 'bonus_north', '0.5', 'Доплата: bonus_north', '2025-11-17 18:27:53');

-- --------------------------------------------------------

--
-- Структура таблицы `shifts`
--

CREATE TABLE `shifts` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `date` date NOT NULL,
  `shift` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `vacation_requests`
--

CREATE TABLE `vacation_requests` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('approved','pending','rejected') DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `vacation_requests`
--

INSERT INTO `vacation_requests` (`id`, `employee_id`, `start_date`, `end_date`, `status`, `created_at`, `reviewed_by`, `reviewed_at`) VALUES
(5, 170, '2025-12-18', '2026-01-16', 'approved', '2025-11-16 07:17:33', NULL, NULL),
(6, 170, '2025-04-13', '2025-05-03', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(8, 171, '2025-06-01', '2025-06-21', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(9, 171, '2025-10-31', '2025-11-19', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(10, 173, '2025-02-09', '2025-03-01', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(11, 173, '2025-06-30', '2025-07-29', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(12, 166, '2025-03-02', '2025-03-22', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(13, 166, '2025-10-05', '2025-11-03', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(14, 165, '2025-07-13', '2025-08-02', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(15, 165, '2025-11-02', '2025-12-01', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(16, 163, '2025-03-18', '2025-04-07', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(17, 163, '2025-09-14', '2025-10-13', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(18, 164, '2025-04-01', '2025-04-21', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(19, 164, '2025-11-09', '2025-12-08', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(20, 169, '2025-05-13', '2025-06-02', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(21, 169, '2025-09-07', '2025-10-06', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(22, 172, '2025-06-08', '2025-06-28', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(23, 172, '2025-11-16', '2025-12-15', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(26, 175, '2025-05-25', '2025-06-14', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(27, 175, '2025-09-14', '2025-10-13', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(28, 159, '2025-03-09', '2025-03-29', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(29, 159, '2025-08-19', '2025-09-17', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(30, 162, '2025-05-04', '2025-05-24', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(31, 162, '2025-08-31', '2025-09-29', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(32, 167, '2025-05-11', '2025-05-31', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(33, 167, '2025-08-31', '2025-09-29', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(34, 168, '2025-02-17', '2025-03-09', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(35, 168, '2025-10-19', '2025-11-17', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(36, 160, '2025-06-01', '2025-06-21', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(37, 160, '2025-10-31', '2025-11-29', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(38, 161, '2025-07-13', '2025-08-02', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(39, 161, '2025-11-03', '2025-12-02', 'approved', '2025-11-16 15:20:09', NULL, NULL),
(41, 174, '2025-04-12', '2025-06-01', 'approved', '2025-11-17 02:10:41', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `work_schedule`
--

CREATE TABLE `work_schedule` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `date` date NOT NULL,
  `shift_type` enum('day','night','off') NOT NULL DEFAULT 'off'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `fk_position` (`position_code`);

--
-- Индексы таблицы `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_month` (`employee_id`,`year`,`month`);

--
-- Индексы таблицы `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`code`);

--
-- Индексы таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_day` (`employee_id`,`date`);

--
-- Индексы таблицы `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Индексы таблицы `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_day` (`employee_id`,`date`);

--
-- Индексы таблицы `vacation_requests`
--
ALTER TABLE `vacation_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vacation_period` (`employee_id`,`start_date`,`end_date`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_vacation_date` (`start_date`,`end_date`),
  ADD KEY `idx_employee_vacation` (`employee_id`);

--
-- Индексы таблицы `work_schedule`
--
ALTER TABLE `work_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date_employee` (`employee_id`,`date`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT для таблицы `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4691;

--
-- AUTO_INCREMENT для таблицы `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT для таблицы `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `vacation_requests`
--
ALTER TABLE `vacation_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT для таблицы `work_schedule`
--
ALTER TABLE `work_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_position` FOREIGN KEY (`position_code`) REFERENCES `positions` (`code`);

--
-- Ограничения внешнего ключа таблицы `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `shifts`
--
ALTER TABLE `shifts`
  ADD CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `vacation_requests`
--
ALTER TABLE `vacation_requests`
  ADD CONSTRAINT `vacation_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vacation_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `work_schedule`
--
ALTER TABLE `work_schedule`
  ADD CONSTRAINT `work_schedule_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
