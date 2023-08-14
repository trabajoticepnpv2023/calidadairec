CREATE TRIGGER `insert_mediciones` AFTER UPDATE ON `mdl_user_info_data` FOR EACH ROW BEGIN
    DECLARE v_userid INT;

    -- Verificar si hubo una actualización en la columna fieldid=8
    IF NEW.fieldid = 8 AND OLD.fieldid = 8 AND NEW.data <> OLD.data THEN
        -- Obtener el userid actualizado
        SET v_userid = NEW.userid;

        -- Convertir el valor de fieldid=3 a un tipo DATE
        SET @fecha_str = (SELECT data FROM mdl_user_info_data WHERE userid = v_userid AND fieldid = 3);
        SET @fecha_date = STR_TO_DATE(@fecha_str, '%d/%m/%Y');
        
        SET @fecha_str = (SELECT data FROM mdl_user_info_data WHERE userid = v_userid AND fieldid = 3);
        SET @hora_str = (SELECT data FROM mdl_user_info_data WHERE userid = v_userid AND fieldid = 8);
        SET @fecha_hora_str = CONCAT(@fecha_str, ' ', @hora_str);
        SET @fecha_hora_datetime = STR_TO_DATE(@fecha_hora_str, '%d/%m/%Y %H:%i');

        -- Convertir los valores de fieldid=2, fieldid=5, fieldid=6 y fieldid=7 a FLOAT con dos decimales
        SET @hum_str = (SELECT data FROM mdl_user_info_data WHERE userid = v_userid AND fieldid = 2);
        SET @hum_float = CAST(@hum_str AS DECIMAL(10, 2));

        SET @temp_str = (SELECT data FROM mdl_user_info_data WHERE userid = v_userid AND fieldid = 5);
        SET @temp_float = CAST(@temp_str AS DECIMAL(10, 2));

        SET @co_str = (SELECT data FROM mdl_user_info_data WHERE userid = v_userid AND fieldid = 6);
        SET @co_float = CAST(@co_str AS DECIMAL(10, 2));

        SET @pm25_str = (SELECT data FROM mdl_user_info_data WHERE userid = v_userid AND fieldid = 7);
        SET @pm25_float = CAST(@pm25_str AS DECIMAL(10, 2));

        -- Insertar los valores en la tabla mdl_mediciones
        
        INSERT INTO mdl_mediciones (userid, TIPO, HUM, FECHA, FECHAHORA, TEMP, CO, PM25)
        SELECT
            v_userid,
            (SELECT data FROM mdl_user_info_data WHERE userid = v_userid AND fieldid = 1),
            @hum_float,
            @fecha_date,
            @fecha_hora_datetime,
            @temp_float,
            @co_float,
            @pm25_float;
    END IF;
END
