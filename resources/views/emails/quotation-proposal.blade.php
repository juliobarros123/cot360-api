<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposta de Seguro</title>
    <style>
        /* Estilos mantidos do exemplo anterior */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
        }
        .steps {
            margin: 25px 0;
        }
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .step-number {
            background-color: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 0.9em;
            color: #666;
            text-align: center;
        }
        .links {
            margin: 25px 0;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Proposta de Seguro</h1>
            <p>Prezado(a) <strong>{{ $proposal->client_name ?? 'Cliente' }}</strong>,</p>
        </div>

        <div class="info-box">
            <p>Enviamos a sua proposta de seguro personalizada. Por favor, siga as instruções abaixo para visualizar e responder à proposta.</p>
        </div>

        <div class="steps">
            <h3>📋 Instruções para aceitação:</h3>
            
            <div class="step">
                <div class="step-number">1</div>
                <div>
                    <strong>Visualize a proposta completa</strong>
                    <p>Clique no link abaixo para ver a sua proposta detalhada em formato PDF:</p>
                    <a href="{{ $proposal->proposal_pdf_url }}" class="button">📄 Ver Proposta em PDF</a>
                </div>
            </div>

            <div class="step">
                <div class="step-number">2</div>
                <div>
                    <strong>Preencha o formulário de aceitação</strong>
                    <p>Para aceitar a proposta, clique no link abaixo e preencha o formulário:</p>
                    <a href="{{ $proposal->proposal_form_url }}" class="button">📝 Acessar Formulário</a>
                </div>
            </div>

            <div class="step">
                <div class="step-number">3</div>
                <div>
                    <strong>Envie a sua resposta</strong>
                    <p>Após preencher o formulário, clique em "Enviar" para finalizar o processo.</p>
                </div>
            </div>
        </div>

        <div class="links">
            <h4>🔗 Links diretos:</h4>
            <p><strong>Proposta em PDF:</strong><br>
            <a href="{{ $proposal->proposal_pdf_url }}">{{ $proposal->proposal_pdf_url }}</a></p>
            
            <p><strong>Formulário de aceitação:</strong><br>
            <a href="{{ $proposal->proposal_form_url }}">{{ $proposal->proposal_form_url }}</a></p>
        </div>

        @if($proposal->message)
        <div class="info-box">
            <h4>💬 Mensagem do corretor:</h4>
            <p><em>"{{ $proposal->message }}"</em></p>
        </div>
        @endif

        <div class="info-box">
            <h4>⚠️ Informações importantes:</h4>
            <ul>
                <li>Esta proposta é válida por <strong>30 dias</strong> a partir do envio</li>
                <li>Mantenha estes links em segurança - eles são únicos para você</li>
                <li>O link do formulário contém um código de segurança para identificação</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>{{ $companyName }}</strong></p>
            <p>📧 Email:
                
                
                {{ $companyContact }}</p>
            <p>📞 Telefone: {{ config('app.contact_phone', '+244 XXX XXX XXX') }}</p>
            <p style="font-size: 0.8em; color: #999; margin-top: 15px;">
                Esta é uma mensagem automática. Para responder, utilize o email de contato acima.
            </p>
        </div>
    </div>
</body>
</html>