<?php
require 'conexao.php';

if (!empty($_SESSION['tipo'])) {
    header('Location: ' . destino_do_perfil($_SESSION['tipo']));
    exit();
}

$erro = '';
$tipo = $_POST['tipo'] ?? 'funcionario';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    $id_emp = (int)($_POST['id_emp'] ?? 0);
    $id_psic = (int)($_POST['id_psic'] ?? 0);
    $emp_nome = trim($_POST['emp_nome'] ?? '');
    $emp_descr = trim($_POST['emp_descr'] ?? '');

    if (!isset(PERFIS[$tipo])) {
        $erro = 'Selecione o tipo de conta.';
    } elseif (($erroDados = validar_dados_basicos($nome, $email, $telefone)) !== '') {
        $erro = $erroDados;
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar) {
        $erro = 'As senhas não conferem.';
    } elseif ($tipo === 'funcionario' && $id_emp === 0) {
        $erro = 'Selecione a empresa.';
    } elseif ($tipo === 'chefe' && ($emp_nome === '' || mb_strlen($emp_nome) > 50 || mb_strlen($emp_descr) > 200)) {
        $erro = 'Informe o nome da empresa (até 50 caracteres) e uma descrição de até 200.';
    }

    if ($erro === '') {
        $erro = mensagem_contato_repetido(contato_em_uso($conn, $email, $telefone));
    }

    if ($erro === '') {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $tel = $telefone === '' ? null : $telefone;

        try {
            if ($tipo === 'funcionario') {
                $psic = $id_psic ?: null;
                $stmt = $conn->prepare("INSERT INTO g07_Funcionario (id_Psic, id_Emp, Nome, Email, Telefone, Ofensiva, Pontuacao, Senha) VALUES (?, ?, ?, ?, ?, 0, 0, ?)");
                $stmt->bind_param('iissss', $psic, $id_emp, $nome, $email, $tel, $hash);
                $stmt->execute();
            } elseif ($tipo === 'chefe') {
                $conn->begin_transaction();
                $stmt = $conn->prepare("INSERT INTO g07_Chefe_Emp (Nome, Email, Telefone, Senha) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('ssss', $nome, $email, $tel, $hash);
                $stmt->execute();
                $id_chefe = $conn->insert_id;

                $stmt = $conn->prepare("INSERT INTO g07_Empresa (id_Chefe, Nome, Descr) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $id_chefe, $emp_nome, $emp_descr);
                $stmt->execute();
                $conn->commit();
            } else {
                $stmt = $conn->prepare("INSERT INTO g07_Psicologo (Nome, Email, Telefone, Senha) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('ssss', $nome, $email, $tel, $hash);
                $stmt->execute();
            }
            header('Location: index.php?cadastro=ok');
            exit();
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            $erro = $ex->getCode() === 1062
                ? 'E-mail ou telefone já cadastrado em outra conta.'
                : 'Não foi possível concluir o cadastro. Confira os dados e tente novamente.';
        }
    }
}

$empresas = $conn->query("SELECT id_Emp, Nome FROM g07_Empresa ORDER BY Nome")->fetch_all(MYSQLI_ASSOC);
$psicologos = $conn->query("SELECT id_Psic, Nome FROM g07_Psicologo ORDER BY Nome")->fetch_all(MYSQLI_ASSOC);

exibir_topo('Criar conta', 'pagina-auth');
?>
<section class="auth auth-larga">
  <h1>Criar conta</h1>
  <p class="apoio">Escolha o tipo de conta e preencha seus dados.</p>

  <?php if ($erro): ?>
    <div class="aviso aviso-erro"><?= e($erro) ?></div>
  <?php endif; ?>
  <div class="aviso aviso-erro" id="aviso-js" hidden></div>

  <form method="POST" action="cadastro.php" id="form-cadastro">
    <fieldset class="segmentado">
      <legend>Tipo de conta</legend>
      <?php foreach (PERFIS as $chave => $p): ?>
        <label>
          <input type="radio" name="tipo" value="<?= $chave ?>" <?= $tipo === $chave ? 'checked' : '' ?>>
          <span><?= e($p['rotulo']) ?></span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <div class="grade-campos">
      <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" maxlength="50" value="<?= v('nome') ?>" required>
      </div>
      <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" maxlength="80" value="<?= v('email') ?>" required>
      </div>
      <div class="campo">
        <label for="telefone">Telefone com DDD</label>
        <input type="text" id="telefone" name="telefone" maxlength="11" inputmode="numeric" data-somente-numeros value="<?= v('telefone') ?>">
      </div>
      <div class="campo"></div>
      <div class="campo">
        <label for="senha">Senha</label>
        <div class="campo-senha">
          <input type="password" id="senha" name="senha" minlength="6" required>
          <button type="button" data-toggle-senha="senha">Mostrar</button>
        </div>
      </div>
      <div class="campo">
        <label for="confirmar">Confirmar senha</label>
        <input type="password" id="confirmar" name="confirmar" minlength="6" required>
      </div>
    </div>

    <div class="grade-campos" data-tipo="funcionario">
      <div class="campo">
        <label for="id_emp">Empresa</label>
        <select id="id_emp" name="id_emp" required>
          <option value="">Selecione</option>
          <?php foreach ($empresas as $emp): ?>
            <option value="<?= $emp['id_Emp'] ?>" <?= ($_POST['id_emp'] ?? '') == $emp['id_Emp'] ? 'selected' : '' ?>><?= e($emp['Nome']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!$empresas): ?>
          <small>Nenhuma empresa cadastrada ainda. O chefe precisa se cadastrar primeiro.</small>
        <?php endif; ?>
      </div>
      <div class="campo">
        <label for="id_psic">Psicólogo de acompanhamento (opcional)</label>
        <select id="id_psic" name="id_psic">
          <option value="">Nenhum por enquanto</option>
          <?php foreach ($psicologos as $ps): ?>
            <option value="<?= $ps['id_Psic'] ?>" <?= ($_POST['id_psic'] ?? '') == $ps['id_Psic'] ? 'selected' : '' ?>><?= e($ps['Nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grade-campos" data-tipo="chefe" hidden>
      <div class="campo">
        <label for="emp_nome">Nome da empresa</label>
        <input type="text" id="emp_nome" name="emp_nome" maxlength="50" value="<?= v('emp_nome') ?>" required>
      </div>
      <div class="campo">
        <label for="emp_descr">Descrição da empresa</label>
        <input type="text" id="emp_descr" name="emp_descr" maxlength="200" value="<?= v('emp_descr') ?>">
      </div>
    </div>

    <button type="submit" class="botao botao-bloco">Criar conta</button>
  </form>

  <p class="rodape-form">Já tem conta? <a href="index.php">Entrar</a></p>
</section>
<?php exibir_rodape(); ?>
