UPDATE ohrm_module
SET status = 0
WHERE name = 'buzz';

UPDATE ohrm_menu_item
SET status = 0
WHERE menu_title = 'Comunicados'
   OR screen_id IN (
     SELECT id
     FROM ohrm_screen
     WHERE module_id IN (
       SELECT id
       FROM ohrm_module
       WHERE name = 'buzz'
     )
   );
