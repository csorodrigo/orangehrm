#!/bin/sh
set -eu

cd /var/www/html

export CIA_FERIAS_DB_HOST="${CIA_FERIAS_DB_HOST:-cia-ferias-db}"
export CIA_FERIAS_DB_PORT="${CIA_FERIAS_DB_PORT:-3306}"
export CIA_FERIAS_DB_NAME="${CIA_FERIAS_DB_NAME:-cia_ferias}"
export CIA_FERIAS_DB_ROOT_USER="${CIA_FERIAS_DB_ROOT_USER:-root}"
export CIA_FERIAS_DB_ROOT_PASSWORD="${CIA_FERIAS_DB_ROOT_PASSWORD:-cia_ferias_root}"
export CIA_FERIAS_DB_USER="${CIA_FERIAS_DB_USER:-cia_ferias}"
export CIA_FERIAS_DB_PASSWORD="${CIA_FERIAS_DB_PASSWORD:-cia_ferias}"

until php -r '$host = getenv("CIA_FERIAS_DB_HOST"); $port = (int) getenv("CIA_FERIAS_DB_PORT"); $socket = @fsockopen($host, $port, $errno, $errstr, 2); if ($socket) { fclose($socket); exit(0); } exit(1);'; do
  sleep 2
done

database_exists() {
  php -r '$dsn = sprintf("mysql:host=%s;port=%s", getenv("CIA_FERIAS_DB_HOST"), getenv("CIA_FERIAS_DB_PORT")); $pdo = new PDO($dsn, getenv("CIA_FERIAS_DB_ROOT_USER"), getenv("CIA_FERIAS_DB_ROOT_PASSWORD")); $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?"); $stmt->execute([getenv("CIA_FERIAS_DB_NAME")]); exit($stmt->fetchColumn() ? 0 : 1);'
}

write_conf_file() {
  php -r '$template = file_get_contents("installer/config/Conf.tpl.php"); $search = ["{{dbHost}}", "{{dbPort}}", "{{dbName}}", "{{dbUser}}", "{{dbPass}}"]; $replace = [getenv("CIA_FERIAS_DB_HOST"), getenv("CIA_FERIAS_DB_PORT"), getenv("CIA_FERIAS_DB_NAME"), getenv("CIA_FERIAS_DB_USER"), getenv("CIA_FERIAS_DB_PASSWORD")]; file_put_contents("lib/confs/Conf.php", str_replace($search, $replace, $template));'
}

seed_cia_ferias_data() {
  php <<'PHP'
<?php
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    getenv('CIA_FERIAS_DB_HOST'),
    getenv('CIA_FERIAS_DB_PORT'),
    getenv('CIA_FERIAS_DB_NAME')
);
$pdo = new PDO($dsn, getenv('CIA_FERIAS_DB_ROOT_USER'), getenv('CIA_FERIAS_DB_ROOT_PASSWORD'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->prepare('UPDATE hs_hr_config SET value = :value WHERE name = :name')
    ->execute(['value' => '', 'name' => 'help.url']);
$pdo->prepare('UPDATE hs_hr_config SET value = :value WHERE name = :name')
    ->execute(['value' => 'pt_BR', 'name' => 'admin.localization.default_language']);
$pdo->prepare('UPDATE cia_ferias_i18n_language SET enabled = 1, added = 1 WHERE code = :code')
    ->execute(['code' => 'pt_BR']);
$pdo->exec(
    'UPDATE cia_ferias_theme
     SET client_logo = NULL,
         client_banner = NULL,
         login_banner = NULL,
         show_social_media_icons = 0,
         client_logo_filename = NULL,
         client_logo_file_type = NULL,
         client_logo_file_size = NULL,
         client_banner_filename = NULL,
         client_banner_file_type = NULL,
         client_banner_file_size = NULL,
         login_banner_filename = NULL,
         login_banner_file_type = NULL,
         login_banner_file_size = NULL'
);

$languageIdStatement = $pdo->prepare('SELECT id FROM cia_ferias_i18n_language WHERE code = :code LIMIT 1');
$languageIdStatement->execute(['code' => 'pt_BR']);
$ptBrLanguageId = $languageIdStatement->fetchColumn();
if ($ptBrLanguageId === false) {
    throw new RuntimeException('pt_BR language pack not found');
}

$translationSelect = $pdo->prepare(
    'SELECT id FROM cia_ferias_i18n_translate WHERE language_id = :languageId AND lang_string_id = :langStringId LIMIT 1'
);
$translationInsert = $pdo->prepare(
    'INSERT INTO cia_ferias_i18n_translate (lang_string_id, language_id, value, customized, modified_at)
     VALUES (:langStringId, :languageId, :value, 1, NOW())'
);
$translationUpdate = $pdo->prepare(
    'UPDATE cia_ferias_i18n_translate SET value = :value, customized = 1, modified_at = NOW() WHERE id = :id'
);
$langStringStatement = $pdo->prepare('SELECT id FROM cia_ferias_i18n_lang_string WHERE unit_id = :unitId');
$translations = [
    'cancel' => 'Cancelar',
    'employment_status' => 'Status do vínculo',
    'half_day' => 'Meio dia',
    'job_categories' => 'Categorias de cargo',
    'job_title' => 'Cargo',
    'job_titles' => 'Cargos',
    'july' => 'Julho',
    'jun' => 'Jun',
    'leave_period' => 'Período de férias',
    'leave_type' => 'Tipo de férias',
    'leave_types' => 'Tipos de férias',
    'login' => 'Entrar',
    'may' => 'Mai',
    'monday' => 'Segunda-feira',
    'non_working_day' => 'Dia não útil',
    'password' => 'Senha',
    'required' => 'Obrigatório',
    'save' => 'Salvar',
    'select' => '-- Selecionar --',
    'start_date' => 'Data de início',
    'start_month' => 'Mês de início',
    'username' => 'Usuário',
];

foreach ($translations as $unitId => $translation) {
    $langStringStatement->execute(['unitId' => $unitId]);
    $langStringIds = $langStringStatement->fetchAll(PDO::FETCH_COLUMN);
    foreach ($langStringIds as $langStringId) {
        $translationSelect->execute(['languageId' => (int) $ptBrLanguageId, 'langStringId' => (int) $langStringId]);
        $translationId = $translationSelect->fetchColumn();
        if ($translationId === false) {
            $translationInsert->execute([
                'languageId' => (int) $ptBrLanguageId,
                'langStringId' => (int) $langStringId,
                'value' => $translation,
            ]);
        } else {
            $translationUpdate->execute([
                'id' => (int) $translationId,
                'value' => $translation,
            ]);
        }
    }
}

$password = password_hash('CiaFerias@2026!', PASSWORD_BCRYPT, ['cost' => 12]);

$roleStatement = $pdo->prepare('SELECT id FROM cia_ferias_user_role WHERE name = :name LIMIT 1');
$employeeSelect = $pdo->prepare('SELECT emp_number FROM hs_hr_employee WHERE employee_id = :employeeId LIMIT 1');
$employeeInsert = $pdo->prepare(
    'INSERT INTO hs_hr_employee (employee_id, emp_lastname, emp_firstname, emp_middle_name, emp_work_email)
     VALUES (:employeeId, :lastName, :firstName, :middleName, :email)'
);
$employeeUpdate = $pdo->prepare(
    'UPDATE hs_hr_employee
     SET emp_lastname = :lastName, emp_firstname = :firstName, emp_middle_name = :middleName, emp_work_email = :email
     WHERE emp_number = :empNumber'
);
$userSelect = $pdo->prepare('SELECT id FROM cia_ferias_user WHERE user_name = :username LIMIT 1');
$userInsert = $pdo->prepare(
    'INSERT INTO cia_ferias_user (user_role_id, emp_number, user_name, user_password, deleted, status, date_entered)
     VALUES (:roleId, :empNumber, :username, :password, 0, 1, NOW())'
);
$userUpdate = $pdo->prepare(
    'UPDATE cia_ferias_user
     SET user_role_id = :roleId, emp_number = :empNumber, user_password = :password, deleted = 0, status = 1,
         date_modified = NOW()
     WHERE id = :id'
);

$users = [
    ['employeeId' => '0002', 'firstName' => 'Joao', 'lastName' => 'Colaborador', 'email' => 'joao.colaborador@cia-ferias.local', 'username' => 'Joao.Colaborador', 'role' => 'ESS'],
    ['employeeId' => '0003', 'firstName' => 'Maria', 'lastName' => 'Gestora', 'email' => 'maria.gestora@cia-ferias.local', 'username' => 'Maria.Gestora', 'role' => 'Supervisor'],
];

foreach ($users as $user) {
    $employeeSelect->execute(['employeeId' => $user['employeeId']]);
    $empNumber = $employeeSelect->fetchColumn();

    if ($empNumber === false) {
        $employeeInsert->execute([
            'employeeId' => $user['employeeId'],
            'firstName' => $user['firstName'],
            'middleName' => '',
            'lastName' => $user['lastName'],
            'email' => $user['email'],
        ]);
        $empNumber = (int) $pdo->lastInsertId();
    } else {
        $empNumber = (int) $empNumber;
        $employeeUpdate->execute([
            'empNumber' => $empNumber,
            'firstName' => $user['firstName'],
            'middleName' => '',
            'lastName' => $user['lastName'],
            'email' => $user['email'],
        ]);
    }

    $roleStatement->execute(['name' => $user['role']]);
    $roleId = $roleStatement->fetchColumn();
    if ($roleId === false && $user['role'] === 'Supervisor') {
        $roleStatement->execute(['name' => 'ESS']);
        $roleId = $roleStatement->fetchColumn();
    }
    if ($roleId === false) {
        throw new RuntimeException(sprintf('Role not found for %s', $user['username']));
    }

    $userSelect->execute(['username' => $user['username']]);
    $userId = $userSelect->fetchColumn();
    $userParams = [
        'roleId' => (int) $roleId,
        'empNumber' => $empNumber,
        'username' => $user['username'],
        'password' => $password,
    ];

    if ($userId === false) {
        $userInsert->execute($userParams);
    } else {
        $userUpdate->execute([
            'id' => (int) $userId,
            'roleId' => $userParams['roleId'],
            'empNumber' => $userParams['empNumber'],
            'password' => $userParams['password'],
        ]);
    }
}

$maxEmpNumber = (int) $pdo->query('SELECT COALESCE(MAX(emp_number), 0) FROM hs_hr_employee')->fetchColumn();
$uniqueIdUpdate = $pdo->prepare(
    'UPDATE hs_hr_unique_id
     SET last_id = GREATEST(last_id, :lastId)
     WHERE table_name = :tableName'
);
$uniqueIdUpdate->execute(['lastId' => $maxEmpNumber, 'tableName' => 'hs_hr_employee']);

echo "CIA Férias demo users ready\n";
PHP
}

if [ ! -f lib/confs/Conf.php ]; then
  if database_exists; then
    write_conf_file
  else
    cat > installer/cli_install_config.yaml <<EOF
database:
  hostName: ${CIA_FERIAS_DB_HOST}
  hostPort: ${CIA_FERIAS_DB_PORT}
  databaseName: ${CIA_FERIAS_DB_NAME}
  privilegedDatabaseUser: ${CIA_FERIAS_DB_ROOT_USER}
  privilegedDatabasePassword: ${CIA_FERIAS_DB_ROOT_PASSWORD}
  useSameDbUserForCiaFerias: n
  ciaFeriasDatabaseUser: ${CIA_FERIAS_DB_USER}
  ciaFeriasDatabasePassword: ${CIA_FERIAS_DB_PASSWORD}
  isExistingDatabase: n
  enableDataEncryption: n

organization:
  name: CIA Férias
  country: BR

admin:
  adminUserName: Admin
  adminPassword: CiaFerias@2026!
  adminEmployeeFirstName: Administrador
  adminEmployeeLastName: Sistema
  workEmail: admin@cia-ferias.local
  contactNumber: ~
  registrationConsent: false

license:
  agree: y
EOF

    php installer/cli_install.php
  fi

  seed_cia_ferias_data
  php bin/console orm:generate-proxies || true
  php bin/console cache:clear || true
  chown -R www-data:www-data lib/confs src/cache src/log src/config
else
  seed_cia_ferias_data
fi

exec docker-php-entrypoint "$@"
