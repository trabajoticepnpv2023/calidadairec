-- Crear una vista que calcula los promedios por hora para cada usuario
CREATE VIEW vista_promedios_hora_userid AS
SELECT 
    -- Generar un número de fila para la vista (es útil para desplegar la información mediante arrays en el frontend)
    ROW_NUMBER() OVER (ORDER BY DATE_FORMAT(FECHAHORA, '%Y-%m-%d %H:00:00')) AS vista_id,
    -- Identificador del usuario
    userid,
    -- Redondear la hora a su inicio más cercano
    DATE_FORMAT(FECHAHORA, '%Y-%m-%d %H:00:00') AS HoraInicio,
    -- Calcular el promedio de la columna de temperatura
    AVG(TEMP) AS Promedio_TEMP,
    -- Calcular el promedio de la columna de monóxido de carbono
    AVG(CO) AS Promedio_CO,
    -- Calcular el promedio de la columna de partículas PM2.5
    AVG(PM25) AS Promedio_PM25,
    -- Calcular el promedio de la columna de humedad
    AVG(HUM) AS Promedio_HUM
FROM mdl_mediciones
-- Agrupar los resultados por identificador de usuario y hora redondeada
GROUP BY userid, HoraInicio
-- Ordenar los resultados por hora
ORDER BY HoraInicio;
