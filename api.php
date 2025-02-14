<?php

// Função principal para processar o CPF
function processar_cpf($cpf) {
    // Credenciais de login
    $email = 'rafaelafelicianitrevisan@gmail.com';
    $senha = 'Vigilancia03152309!';
    $credentials = "$email:$senha";
    $credentials_base64 = base64_encode($credentials);

    // URLs da API
    $url_login = 'https://servicos-cloud.saude.gov.br/pni-bff/v1/autenticacao/tokenAcesso';
    $url_pesquisa_base = 'https://servicos-cloud.saude.gov.br/pni-bff/v1/cidadao/cpf/';

    // Cabeçalhos para login
    $headers_login = [
        "Host: servicos-cloud.saude.gov.br",
        "Connection: keep-alive",
        "Content-Length: 0",
        "accept: application/json",
        "X-Authorization: Basic $credentials_base64",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        "Origin: https://si-pni.saude.gov.br",
        "Referer: https://si-pni.saude.gov.br/"
    ];

    // Configurações de tentativa
    $max_retries = 3;
    $retry_delay = 5;

    // Tentativas para login
    for ($i = 0; $i < $max_retries; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_login);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_login);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response_login = curl_exec($ch);

        if ($response_login === false) {
            curl_close($ch);
            sleep($retry_delay);
            continue;
        }

        curl_close($ch);
        $login_data = json_decode($response_login, true);

        if (isset($login_data['accessToken'])) {
            $token_acesso = $login_data['accessToken'];
            $url_pesquisa = $url_pesquisa_base . $cpf;

            // Cabeçalhos para a consulta de CPF
            $headers_pesquisa = [
                "Authorization: Bearer $token_acesso",
                "accept: application/json",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
                "Origin: https://si-pni.saude.gov.br",
                "Referer: https://si-pni.saude.gov.br/"
            ];

            // Tentativas para consulta
            for ($j = 0; $j < $max_retries; $j++) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url_pesquisa);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_pesquisa);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                $response_pesquisa = curl_exec($ch);

                if ($response_pesquisa === false) {
                    curl_close($ch);
                    sleep($retry_delay);
                    continue;
                }

                curl_close($ch);
                $dados_pessoais = json_decode($response_pesquisa, true);

                if (isset($dados_pessoais['records'])) {
                    return formatar_informacoes($dados_pessoais['records'][0]);
                } else {
                    return json_encode(["error" => "Erro na pesquisa", "details" => $response_pesquisa]);
                }
            }

            return json_encode(["error" => "Falha na requisição de pesquisa após várias tentativas"]);
        } else {
            return json_encode(["error" => "Erro no login", "details" => $response_login]);
        }
    }

    return json_encode(["error" => "Falha na requisição de login após várias tentativas"]);
}

// Função para formatar os dados da API
function formatar_informacoes($dados_pessoais) {
    return json_encode([
        'nome' => $dados_pessoais['nome'] ?? null,
        'dataNascimento' => $dados_pessoais['dataNascimento'] ?? null,
        'sexo' => $dados_pessoais['sexo'] ?? null,
        'nomeMae' => $dados_pessoais['nomeMae'] ?? null,
        'nomePai' => $dados_pessoais['nomePai'] ?? null,
        'ativo' => $dados_pessoais['ativo'] ?? null,
        'obito' => $dados_pessoais['obito'] ?? null,
        'racaCor' => $dados_pessoais['racaCor'] ?? null,
        'telefone' => $dados_pessoais['telefone'] ?? null,
        'endereco' => $dados_pessoais['endereco'] ?? null,
    ]);
}

// Cabeçalhos para saída JSON
header('Content-Type: application/json');

// Verificação de parâmetro CPF
if (isset($_GET['cpf'])) {
    $cpf = $_GET['cpf'];
    echo processar_cpf($cpf);
} else {
    echo json_encode(["error" => "Por favor, forneça o CPF na URL como ?cpf=seu_cpf"]);
}

?>
