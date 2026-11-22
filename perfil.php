<?php
require 'conexao.php';

if (empty($_SESSION['tipo'])) {
    header('Location: index.php');
    exit();
}

$tipo = $_SESSION['tipo'];
$id = (int)$_SESSION['id'];
$tabela = PERFIS[$tipo]['tabela'];
$coluna = PERFIS[$tipo]['id'];

$stmt = $conn->prepare("SELECT Nome, Email, Telefone FROM $tabela WHERE $coluna = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();

$erro = '';
$erroSenha = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'dados') {
        $nome = trim($_POST['nome'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
        $d = ['Nome' => $nome, 'Email' => $email, 'Telefone' => $telefone];

        $erro = validar_dados_basicos($nome, $email, $telefone);
        if ($erro === '') {
            $erro = mensagem_contato_repetido(contato_em_uso($conn, $email, $telefone, $tipo, $id));
        }
        if ($erro === '') {
            $tel = $telefone === '' ? null : $telefone;
            try {
                $stmt = $conn->prepare("UPDATE $tabela SET Nome = ?, Email = ?, Telefone = ? WHERE $coluna = ?");
                $stmt->bind_param('sssi', $nome, $email, $tel, $id);
                $stmt->execute();
                $_SESSION['nome'] = $nome;
                definir_aviso('ok', 'Dados atualizados.');
                header('Location: perfil.php');
                exit();
            } catch (mysqli_sql_exception $ex) {
                $erro = 'Não foi possível salvar os dados. Tente novamente.';
            }
        }
    } elseif ($acao === 'senha') {
        $atual = $_POST['atual'] ?? '';
        $nova = $_POST['nova'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        $stmt = $conn->prepare("SELECT Senha FROM $tabela WHERE $coluna = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $hashAtual = $stmt->get_result()->fetch_row()[0];

        if (!verificar_senha($atual, $hashAtual)) {
            $erroSenha = 'A senha atual está incorreta.';
        } elseif (strlen($nova) < 6) {
            $erroSenha = 'A nova senha deve ter pelo menos 6 caracteres.';
        } elseif ($nova !== $confirmar) {
            $erroSenha = 'As senhas novas não conferem.';
        } else {
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE $tabela SET Senha = ? WHERE $coluna = ?");
            $stmt->bind_param('si', $hash, $id);
            $stmt->execute();
            definir_aviso('ok', 'Senha alterada.');
            header('Location: perfil.php');
            exit();
        }
    }
}

exibir_topo('Meu perfil');
?>
<h1>Meu perfil</h1>
<p class="apoio"><?= e(PERFIS[$tipo]['rotulo']) ?></p>
<?php exibir_aviso(); ?>

<section class="duas-iguais">
  <div class="bloco">
    <h2>Dados pessoais</h2>
    <?php if ($erro): ?><div class="aviso aviso-erro"><?= e($erro) ?></div><?php endif; ?>
    <form method="POST" action="perfil.php">
      <input type="hidden" name="acao" value="dados">
      <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" maxlength="50" value="<?= e($d['Nome']) ?>" required>
      </div>
      <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" maxlength="80" value="<?= e($d['Email']) ?>" required>
      </div>
      <div class="campo">
        <label for="telefone">Telefone com DDD</label>
        <input type="text" id="telefone" name="telefone" maxlength="11" inputmode="numeric" data-somente-numeros value="<?= e($d['Telefone']) ?>">
      </div>
      <button type="submit" class="botao">Salvar dados</button>
    </form>
  </div>

  <div class="bloco">
    <h2>Trocar senha</h2>
    <?php if ($erroSenha): ?><div class="aviso aviso-erro"><?= e($erroSenha) ?></div><?php endif; ?>
    <form method="POST" action="perfil.php">
      <input type="hidden" name="acao" value="senha">
      <div class="campo">
        <label for="atual">Senha atual</label>
        <input type="password" id="atual" name="atual" required>
      </div>
      <div class="campo">
        <label for="nova">Nova senha</label>
        <input type="password" id="nova" name="nova" minlength="6" required>
      </div>
      <div class="campo">
        <label for="confirmar">Confirmar nova senha</label>
        <input type="password" id="confirmar" name="confirmar" minlength="6" required>
      </div>
      <button type="submit" class="botao">Alterar senha</button>
    </form>
  </div>
</section>
<?php exibir_rodape(); ?>
