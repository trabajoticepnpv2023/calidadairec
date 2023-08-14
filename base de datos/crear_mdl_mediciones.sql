CREATE TABLE `mdl_mediciones` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) DEFAULT NULL,
  `TIPO` varchar(255) DEFAULT NULL,
  `HUM` float(10,2) DEFAULT NULL,
  `FECHA` date DEFAULT NULL,
  `FECHAHORA` datetime DEFAULT NULL,
  `TEMP` float(10,2) DEFAULT NULL,
  `CO` float(10,2) DEFAULT NULL,
  `PM25` float(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Indices de la tabla `mdl_mediciones`
--
ALTER TABLE `mdl_mediciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`);

ALTER TABLE `mdl_mediciones`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `mdl_mediciones`
  ADD CONSTRAINT `mdl_mediciones_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `mdl_user_info_data` (`userid`) ON DELETE CASCADE;
