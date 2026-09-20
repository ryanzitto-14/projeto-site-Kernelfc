<?php
if (!session_id()) session_start();

// Configura o cabeçalho para responder no formato JSON (padrão para requisições assíncronas)
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

// Verifica se o e-mail foi enviado por GET ou POST para dar mais flexibilidade
$email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
}

// Se nenhum e-mail válido for recebido, retorna erro
if (!$email) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Endereço de e-mail inválido ou não fornecido.'
    ]);
    exit;
}

try {
    // Consulta o banco de dados buscando o status de verificação do integrante
    $sql = "SELECT nome, email_verificado FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Retorna o status real do banco de dados (convertendo para booleano puro)
        echo json_encode([
            'sucesso' => true,
            'cadastrado' => true,
            'nome' => $usuario['nome'],
            'verificado' => (int)$usuario['email_verificado'] === 1
        ]);
        exit;
    } else {
        // Caso o e-mail não exista na base de dados da Kernel
        echo json_encode([
            'sucesso' => true,
            'cadastrado' => false,
            'verificado' => false,
            'mensagem' => 'Este e-mail ainda não está cadastrado no sistema da Kernel.'
        ]);
        exit;
    }

} catch (PDOException $e) {
    // Trata erros inesperados no banco de dados com segurança
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno no servidor ao verificar o e-mail.'
    ]);
    exit;
}