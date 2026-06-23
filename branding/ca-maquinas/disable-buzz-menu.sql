UPDATE cia_ferias_module
SET status = 0
WHERE name = 'buzz';

UPDATE cia_ferias_menu_item
SET status = 0
WHERE menu_title = 'Comunicados'
   OR screen_id IN (
     SELECT id
     FROM cia_ferias_screen
     WHERE module_id IN (
       SELECT id
       FROM cia_ferias_module
       WHERE name = 'buzz'
     )
   );

UPDATE cia_ferias_menu_item
SET status = 0
WHERE parent_id IS NULL
  AND menu_title IN ('Ponto', 'Meus Dados', 'Desempenho', 'Time', 'My Info', 'Performance');

UPDATE cia_ferias_menu_item
SET order_hint = CASE menu_title
  WHEN 'Administração' THEN 200
  WHEN 'Admin' THEN 200
  WHEN 'Colaboradores' THEN 300
  WHEN 'PIM' THEN 300
  WHEN 'Férias' THEN 400
  WHEN 'Leave' THEN 400
  WHEN 'Recrutamento' THEN 500
  WHEN 'Recruitment' THEN 500
  WHEN 'Manutenção' THEN 600
  WHEN 'Maintenance' THEN 600
  WHEN 'Solicitações' THEN 700
  WHEN 'Claim' THEN 700
  ELSE order_hint
END
WHERE parent_id IS NULL;

UPDATE cia_ferias_menu_item
SET order_hint = CASE id
  WHEN 82 THEN 100
  WHEN 1 THEN 200
  WHEN 30 THEN 300
  WHEN 41 THEN 400
  WHEN 65 THEN 500
  WHEN 96 THEN 600
  WHEN 105 THEN 700
  ELSE order_hint
END
WHERE parent_id IS NULL;

UPDATE hs_hr_employee
SET work_station = 2
WHERE emp_number IN (3, 4, 5, 8, 9);

UPDATE hs_hr_employee
SET work_station = 3
WHERE emp_number IN (6, 7, 10);

UPDATE cia_ferias_subunit
SET unit_id = 'MAT',
    name = 'Matriz',
    description = NULL,
    lft = 2,
    rgt = 3,
    level = 1
WHERE id = 2;

UPDATE cia_ferias_subunit
SET unit_id = 'ADM',
    name = 'administração',
    description = NULL,
    lft = 4,
    rgt = 5,
    level = 1
WHERE id = 3;

UPDATE cia_ferias_subunit
SET lft = 1,
    rgt = 6,
    level = 0
WHERE id = 1;

DELETE FROM cia_ferias_subunit
WHERE id IN (4, 5, 6);
