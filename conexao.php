<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();

// Logout: acessado por index.php?sair=1
if (isset($_GET['sair'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit();
}

// Banco da turma (Hostinger). Não publique este arquivo em repositório público.
const DB_HOST = 'auth-db1545.hstgr.io';
const DB_USER = 'u704608208_turma_tarde';
const DB_PASS = '3176@fatecSCS';
const DB_NAME = 'u704608208_turma_tarde';

// Pontuação mínima (de 0 a 15) para sugerir acompanhamento psicológico
const LIMITE_ALERTA = 8;

const PERFIS = [
    'funcionario' => ['tabela' => 'g07_Funcionario', 'id' => 'id_Func', 'rotulo' => 'Funcionário', 'destino' => 'painel_funcionario.php'],
    'chefe'       => ['tabela' => 'g07_Chefe_Emp',   'id' => 'id_Chefe', 'rotulo' => 'Chefe',       'destino' => 'painel_chefe.php'],
    'psicologo'   => ['tabela' => 'g07_Psicologo',   'id' => 'id_Psic',  'rotulo' => 'Psicólogo',   'destino' => 'painel_psicologo.php'],
];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (mysqli_sql_exception $ex) {
    die('Falha na conexão: ' . $ex->getMessage());
}
$conn->set_charset('utf8mb4');

// Cria as tabelas do grupo (prefixo g07_) apenas se ainda não existirem.
// Tabelas que já existem não são alteradas. A verificação roda uma vez por sessão.
function garantir_tabelas($conn) {
    $tabelas = [
        "CREATE TABLE IF NOT EXISTS g07_Psicologo(
            id_Psic INT AUTO_INCREMENT PRIMARY KEY,
            Nome VARCHAR(50) NOT NULL,
            Email VARCHAR(80) UNIQUE,
            Telefone VARCHAR(11) UNIQUE,
            Senha VARCHAR(255) NOT NULL,
            Camin_Img VARCHAR(255)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS g07_Chefe_Emp(
            id_Chefe INT AUTO_INCREMENT PRIMARY KEY,
            Nome VARCHAR(50) NOT NULL,
            Email VARCHAR(80) UNIQUE,
            Telefone VARCHAR(11) UNIQUE,
            Senha VARCHAR(255) NOT NULL,
            Camin_Img VARCHAR(255)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS g07_Empresa(
            id_Emp INT AUTO_INCREMENT PRIMARY KEY,
            id_Chefe INT,
            Nome VARCHAR(50) NOT NULL,
            Descr VARCHAR(200),
            FOREIGN KEY (id_Chefe) REFERENCES g07_Chefe_Emp(id_Chefe)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS g07_Funcionario(
            id_Func INT AUTO_INCREMENT PRIMARY KEY,
            id_Psic INT,
            id_Emp INT,
            Nome VARCHAR(50) NOT NULL,
            Email VARCHAR(80) UNIQUE,
            Telefone VARCHAR(11) UNIQUE,
            Senha VARCHAR(255) NOT NULL,
            Ofensiva INT DEFAULT 0,
            Pontuacao INT DEFAULT 0,
            Camin_Img VARCHAR(255),
            FOREIGN KEY (id_Emp) REFERENCES g07_Empresa(id_Emp),
            FOREIGN KEY (id_Psic) REFERENCES g07_Psicologo(id_Psic)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS g07_Tentativas(
            id_Tent INT AUTO_INCREMENT PRIMARY KEY,
            id_Func INT,
            Dat_Ini DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            Dat_Fim DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_Func) REFERENCES g07_Funcionario(id_Func)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS g07_Questionario(
            id_Quest INT AUTO_INCREMENT PRIMARY KEY,
            Titulo VARCHAR(30) NOT NULL,
            Descri VARCHAR(100)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS g07_Perguntas(
            id_Perg INT AUTO_INCREMENT PRIMARY KEY,
            id_Quest INT,
            Texto VARCHAR(200) NOT NULL,
            Tipo VARCHAR(12),
            Numer INT NOT NULL,
            FOREIGN KEY (id_Quest) REFERENCES g07_Questionario(id_Quest)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS g07_Opcoes(
            id_Op INT AUTO_INCREMENT PRIMARY KEY,
            id_Perg INT,
            Op_Texto VARCHAR(250) NOT NULL,
            Valor INT NOT NULL DEFAULT 0,
            FOREIGN KEY (id_Perg) REFERENCES g07_Perguntas(id_Perg)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS g07_Resp_Quest(
            id_Res INT AUTO_INCREMENT PRIMARY KEY,
            id_Tent INT,
            id_Quest INT,
            id_Op INT,
            id_Perg INT,
            FOREIGN KEY (id_Tent) REFERENCES g07_Tentativas(id_Tent),
            FOREIGN KEY (id_Quest) REFERENCES g07_Questionario(id_Quest),
            FOREIGN KEY (id_Op) REFERENCES g07_Opcoes(id_Op),
            FOREIGN KEY (id_Perg) REFERENCES g07_Perguntas(id_Perg)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tabelas as $sql) {
        $conn->query($sql);
    }

    // Questionário padrão: só é inserido se a tabela de questionários estiver vazia
    if ((int)$conn->query("SELECT COUNT(*) FROM g07_Questionario")->fetch_row()[0] === 0) {
        $conn->begin_transaction();
        $conn->query("INSERT INTO g07_Questionario (id_Quest, Titulo, Descri) VALUES
            (1, 'Bem-estar no trabalho', 'Cinco perguntas rápidas sobre como você tem se sentido nos últimos dias.')");
        $conn->query("INSERT INTO g07_Perguntas (id_Perg, id_Quest, Texto, Tipo, Numer) VALUES
            (1, 1, 'Como você avalia seu nível de estresse nos últimos dias?', 'alternativa', 1),
            (2, 1, 'Como tem sido a qualidade do seu sono?', 'alternativa', 2),
            (3, 1, 'Com que frequência você se sentiu sem energia ou motivação para trabalhar?', 'alternativa', 3),
            (4, 1, 'Como está sua carga de trabalho atualmente?', 'alternativa', 4),
            (5, 1, 'Você tem se sentido apoiado(a) pela equipe e pela liderança?', 'alternativa', 5)");
        $conn->query("INSERT INTO g07_Opcoes (id_Perg, Op_Texto, Valor) VALUES
            (1, 'Tranquilo(a)', 0), (1, 'Um pouco estressado(a)', 1), (1, 'Bastante estressado(a)', 2), (1, 'No limite', 3),
            (2, 'Durmo bem todas as noites', 0), (2, 'Tenho algumas noites ruins', 1), (2, 'A maioria das noites é ruim', 2), (2, 'Quase não consigo dormir', 3),
            (3, 'Nunca', 0), (3, 'Poucas vezes', 1), (3, 'Com frequência', 2), (3, 'Quase todos os dias', 3),
            (4, 'Adequada', 0), (4, 'Um pouco acima do que consigo', 1), (4, 'Muito acima do que consigo', 2), (4, 'Impossível de cumprir', 3),
            (5, 'Sempre', 0), (5, 'Na maioria das vezes', 1), (5, 'Raramente', 2), (5, 'Nunca', 3)");
        $conn->commit();
    }
}

// Colunas que o sistema usa e que podem faltar em tabelas criadas antes.
// Só adiciona quando a coluna não existe; nada é apagado ou alterado.
function garantir_colunas($conn) {
    $necessarias = [
        ['g07_Funcionario', 'Ofensiva',  'INT DEFAULT 0'],
        ['g07_Funcionario', 'Pontuacao', 'INT DEFAULT 0'],
        ['g07_Opcoes',      'Valor',     'INT NOT NULL DEFAULT 0'],
    ];
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    foreach ($necessarias as [$tabela, $coluna, $definicao]) {
        $stmt->bind_param('ss', $tabela, $coluna);
        $stmt->execute();
        $existe = (int)$stmt->get_result()->fetch_row()[0];
        if ($existe === 0) {
            $conn->query("ALTER TABLE $tabela ADD COLUMN $coluna $definicao");
        }
    }
}

// Senhas antigas do banco estão em SHA-1 (40 caracteres hexadecimais).
// As novas usam password_hash. As duas formas são aceitas no login.
function senha_legada($hash) {
    return strlen((string)$hash) === 40 && ctype_xdigit((string)$hash);
}

function verificar_senha($senha, $hash) {
    if (senha_legada($hash)) {
        return hash_equals(strtolower($hash), sha1($senha));
    }
    return password_verify($senha, (string)$hash);
}

// Aumente este valor sempre que a verificação acima mudar, para ela rodar de novo nas sessões abertas
const VERSAO_ESQUEMA = 'v2';

if (($_SESSION['esquema_ok'] ?? '') !== VERSAO_ESQUEMA) {
    try {
        garantir_tabelas($conn);
        garantir_colunas($conn);
        $_SESSION['esquema_ok'] = VERSAO_ESQUEMA;
    } catch (mysqli_sql_exception $ex) {
        die('Não foi possível preparar as tabelas: ' . $ex->getMessage());
    }
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function v($campo) {
    return e($_POST[$campo] ?? '');
}

function exigir_login($tipo) {
    if (empty($_SESSION['tipo']) || $_SESSION['tipo'] !== $tipo) {
        header('Location: index.php');
        exit();
    }
}

function destino_do_perfil($tipo) {
    return PERFIS[$tipo]['destino'] ?? 'index.php';
}

function zerar_ofensivas($conn) {
    $ontem = date('Y-m-d', strtotime('-1 day'));
    $stmt = $conn->prepare(
        "UPDATE g07_Funcionario f SET f.Ofensiva = 0
         WHERE f.Ofensiva > 0
         AND COALESCE((SELECT MAX(DATE(t.Dat_Ini)) FROM g07_Tentativas t WHERE t.id_Func = f.id_Func), '1970-01-01') < ?"
    );
    $stmt->bind_param('s', $ontem);
    $stmt->execute();
}

function fmt_data($data) {
    return $data ? date('d/m/Y H:i', strtotime($data)) : '—';
}

function selo_status($pontuacao, $ultima) {
    if ($ultima === null) {
        return '<span class="selo selo-neutro">Sem respostas</span>';
    }
    if ((int)$pontuacao >= LIMITE_ALERTA) {
        return '<span class="selo selo-alerta">Atenção</span>';
    }
    return '<span class="selo selo-ok">Estável</span>';
}

function pontuacao_maxima($conn) {
    $r = $conn->query(
        "SELECT COALESCE(SUM(m.maximo), 0) FROM (
            SELECT MAX(o.Valor) AS maximo FROM g07_Opcoes o
            JOIN g07_Perguntas p ON p.id_Perg = o.id_Perg
            WHERE p.id_Quest = (SELECT MIN(id_Quest) FROM g07_Questionario)
            GROUP BY o.id_Perg
         ) m"
    );
    return (int)$r->fetch_row()[0];
}

function validar_dados_basicos($nome, $email, $telefone) {
    if ($nome === '' || mb_strlen($nome) > 50) {
        return 'Informe um nome de até 50 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 80) {
        return 'Informe um e-mail válido de até 80 caracteres.';
    }
    if ($telefone !== '' && !preg_match('/^\d{10,11}$/', $telefone)) {
        return 'O telefone deve ter 10 ou 11 dígitos, com DDD.';
    }
    return '';
}

function contato_em_uso($conn, $email, $telefone, $tipoProprio = null, $idProprio = 0) {
    foreach (PERFIS as $tipo => $p) {
        $ignorar = $tipo === $tipoProprio ? (int)$idProprio : 0;
        foreach (['Email' => $email, 'Telefone' => $telefone] as $campo => $valor) {
            if ($valor === '' || $valor === null) {
                continue;
            }
            $stmt = $conn->prepare("SELECT 1 FROM {$p['tabela']} WHERE {$p['id']} <> ? AND $campo = ?");
            $stmt->bind_param('is', $ignorar, $valor);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return $campo;
            }
        }
    }
    return null;
}

function mensagem_contato_repetido($campo) {
    if ($campo === 'Email') {
        return 'Este e-mail já está cadastrado em outra conta.';
    }
    if ($campo === 'Telefone') {
        return 'Este telefone já está cadastrado em outra conta.';
    }
    return '';
}

function definir_aviso($classe, $mensagem) {
    $_SESSION['aviso'] = [$classe, $mensagem];
}

function exibir_aviso() {
    if (empty($_SESSION['aviso'])) {
        return;
    }
    [$classe, $mensagem] = $_SESSION['aviso'];
    unset($_SESSION['aviso']);
    echo '<div class="aviso aviso-' . e($classe) . '">' . e($mensagem) . '</div>';
}

// Cabeçalho comum das páginas (antes ficava em topo.php)
function exibir_topo($titulo, $classe = '') {
    $logado = !empty($_SESSION['tipo']);
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo) ?> | Gestão Preventiva</title>
<link rel="stylesheet" href="css/estilo.css">
</head>
<body class="<?= e($classe) ?>">
<header class="topo">
  <a class="marca" href="<?= $logado ? e(destino_do_perfil($_SESSION['tipo'])) : 'index.php' ?>">Gestão Preventiva</a>
  <?php if ($logado): ?>
  <nav class="menu">
    <?php if ($_SESSION['tipo'] === 'funcionario'): ?>
      <a href="painel_funcionario.php">Meu painel</a>
      <a href="questionario.php">Questionário</a>
    <?php endif; ?>
    <a href="perfil.php">Meu perfil</a>
    <span class="usuario"><?= e($_SESSION['nome']) ?> (<?= e(PERFIS[$_SESSION['tipo']]['rotulo']) ?>)</span>
    <a class="botao botao-sec" href="index.php?sair=1">Sair</a>
  </nav>
  <?php endif; ?>
</header>
<main class="conteudo">
<?php
}

// Rodapé comum das páginas (antes ficava em rodape.php)
function exibir_rodape() {
    ?>
</main>
<footer class="rodape">Projeto Integrador · Gestão Preventiva</footer>
<script src="js/app.js"></script>
</body>
</html>
<?php
}
