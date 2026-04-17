<?php
/**
 * NYXACORE Landing — API PHP pra admin CMS.
 * Substitui o server.js em hostings cPanel sem Node.js.
 *
 * Endpoints:
 *   GET  api.php?action=content     → retorna content.json
 *   POST api.php?action=content     → salva content.json (requer token)
 *   POST api.php?action=login       → valida token
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ═══ Config ═══
define('TOKEN_FILE', __DIR__ . '/.admin_token');
define('CONTENT_FILE', __DIR__ . '/content.json');

function getAdminToken() {
    if (file_exists(TOKEN_FILE)) {
        $t = trim(file_get_contents(TOKEN_FILE));
        if ($t) return $t;
    }
    return getenv('ADMIN_TOKEN') ?: 'nyxacore2026';
}

function saveAdminToken($newToken) {
    file_put_contents(TOKEN_FILE, $newToken, LOCK_EX);
    // Proteger o arquivo via .htaccess se possível
    $htaccess = __DIR__ . '/.htaccess';
    $rule = "\n<Files \".admin_token\">\nOrder Allow,Deny\nDeny from all\n</Files>\n";
    if (!file_exists($htaccess) || strpos(file_get_contents($htaccess), '.admin_token') === false) {
        file_put_contents($htaccess, $rule, FILE_APPEND | LOCK_EX);
    }
}

// ═══ Helpers ═══
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getAuthToken() {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (str_starts_with($header, 'Bearer ')) {
        return substr($header, 7);
    }
    return $_GET['token'] ?? '';
}

function readContent() {
    $defaults = getDefaultContent();
    if (file_exists(CONTENT_FILE)) {
        $data = json_decode(file_get_contents(CONTENT_FILE), true);
        if (is_array($data)) {
            // Merge: saved data wins, defaults fill missing keys
            return array_replace_recursive($defaults, $data);
        }
    }
    return $defaults;
}

function writeContent($data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    file_put_contents(CONTENT_FILE, $json, LOCK_EX);
}

function getRequestBody() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

function getDefaultContent() {
    return [
        'hero' => [
            'badge' => 'Sistema operacional para provedores',
            'title_line1' => 'Infraestrutura',
            'title_accent' => 'inteligente',
            'title_line2' => 'para quem conecta o Brasil.',
            'description' => 'Suite completa de software para ISPs — monitoramento, gestão de CPE, business intelligence, QoS, mapas de rede, notificações, chatbot IA e muito mais.',
            'cta_primary' => 'Agendar demonstração',
            'cta_secondary' => 'Conhecer os produtos',
            'stats' => [
                ['number' => '9', 'label' => 'Produtos integrados'],
                ['number' => '24/7', 'label' => 'Monitoramento real-time'],
                ['number' => 'ML', 'label' => 'Previsão inteligente'],
                ['number' => 'SaaS', 'label' => 'Instalação em minutos'],
            ],
        ],
        'features' => [
            'tag' => 'Por que NYXACORE',
            'title' => 'Construído para quem opera de verdade.',
            'description' => 'Não é mais um dashboard bonito. É infraestrutura que prevê problemas antes deles acontecerem.',
            'items' => [
                ['icon' => '⚡', 'title' => 'Instalação em 5 minutos', 'description' => 'Um comando e tudo sobe — Zabbix, banco, frontend, collector. Setup wizard guia o resto.'],
                ['icon' => '☁️', 'title' => 'SaaS multi-VPS', 'description' => 'Instale em qualquer servidor. Sem hardcode, sem dependência. Cada cliente isolado.'],
                ['icon' => '🧠', 'title' => 'ML preditivo integrado', 'description' => 'Prophet, Holt-Winters e Isolation Forest treinando sobre seus dados. Alertas antes do problema.'],
                ['icon' => '📱', 'title' => '5 canais de notificação', 'description' => 'Telegram, Email, WhatsApp, Webhook e SMS. Escalonamento automático com plantão rotativo.'],
                ['icon' => '🌐', 'title' => 'Multi-site hierárquico', 'description' => 'Organize em árvore: Estado → Cidade → POP → Rack. Cada site com seu Zabbix remoto.'],
                ['icon' => '🔑', 'title' => 'Licenciamento flexível', 'description' => 'Licença por dispositivos. Trial grátis 14 dias. Planos que crescem com o provedor.'],
            ],
        ],
        'products' => [
            ['code' => 'NW', 'name' => 'NyxaWatch', 'desc' => 'Monitoramento completo da rede — dashboards customizáveis, mapas interativos com Leaflet, NetFlow, detecção de anomalias com ML, alertas preditivos e 14 tipos de widgets.', 'tags' => 'NMS · Zabbix Wrapper · Prophet ML'],
            ['code' => 'NA', 'name' => 'NyxaACS', 'desc' => 'Gestão TR-069 de CPEs com auto-provisionamento, autocura inteligente via IA, e integração com GenieACS.', 'tags' => 'TR-069 · GenieACS · Autocura IA'],
            ['code' => 'NI', 'name' => 'NyxaInsight', 'desc' => 'Business Intelligence desenhado para ISPs. Conecta com HubSoft, IXC, SGP e MK-Auth.', 'tags' => 'BI · Multi-ERP · Analytics'],
            ['code' => 'NM', 'name' => 'NyxaMap', 'desc' => 'Mapeamento de rede óptica — fusões, splitters, CTOs, cabos. Visualize toda a infraestrutura FTTH no mapa.', 'tags' => 'FTTH · Mapeamento · GIS'],
            ['code' => 'NF', 'name' => 'NyxaFlow', 'desc' => 'Otimização de QoS e tráfego com CAKE, BBR e eBPF/XDP. Controle fino por assinante com DPI.', 'tags' => 'QoS · eBPF · DPI · Traffic Shaping'],
            ['code' => 'NH', 'name' => 'NyxaHotspot', 'desc' => 'Hotspot centralizado multi-tenant com FreeRADIUS e MikroTik. Portal captivo personalizável e vouchers.', 'tags' => 'WiFi · FreeRADIUS · Multi-tenant'],
            ['code' => 'NB', 'name' => 'NyxaBot', 'desc' => 'Atendente IA multi-canal — WhatsApp, Telegram e integração direta com HubSoft. Persona "Layla".', 'tags' => 'IA · WhatsApp · HubSoft · Layla'],
            ['code' => 'NO', 'name' => 'NyxaOrion', 'desc' => 'Provisionamento e automação de rede — orquestra configurações, deploys e atualizações em larga escala.', 'tags' => 'Provisioning · Automation · Workflows'],
            ['code' => 'NX', 'name' => 'NyxaFlix', 'desc' => 'Plataforma de cursos no formato Netflix — área de alunos, certificados, progresso, streaming de vídeo.', 'tags' => 'E-Learning · Streaming · Certificados'],
        ],
        'pricing' => [
            ['name' => 'Starter', 'price' => 'R$ 297', 'period' => '/mês', 'note' => 'Até 500 dispositivos monitorados', 'featured' => false, 'features' => ['NyxaWatch (monitoramento completo)', 'Dashboards customizáveis', 'Mapas de rede canvas', 'Alertas por Telegram + Email', '1 site (POP único)', 'Suporte por email'], 'cta' => 'Começar trial grátis'],
            ['name' => 'Professional', 'price' => 'R$ 697', 'period' => '/mês', 'note' => 'Até 5.000 dispositivos', 'featured' => true, 'features' => ['Tudo do Starter +', 'NyxaACS (gestão TR-069)', 'NyxaInsight (BI)', 'NetFlow + detecção de anomalias', 'WhatsApp + Webhook + SMS', 'Multi-site (até 10 sites)', 'ML forecasting básico', 'Suporte prioritário'], 'cta' => 'Começar trial grátis'],
            ['name' => 'Enterprise', 'price' => 'Sob consulta', 'period' => '', 'note' => 'Dispositivos ilimitados', 'featured' => false, 'features' => ['Tudo do Professional +', 'Todos os 9 produtos', 'Sites ilimitados + federação', 'ML avançado + AI Insights', 'Integração ERP customizada', 'SLA dedicado + gerente de conta', 'Treinamento presencial', 'Instalação assistida'], 'cta' => 'Falar com especialista'],
        ],
        'testimonials' => [
            ['quote' => 'Antes a gente levava horas pra descobrir onde o problema tava. Com o NyxaWatch, o sistema avisa antes do cliente ligar reclamando.', 'name' => 'Ricardo Costa', 'role' => 'CTO — FibraNet Telecom', 'initials' => 'RC'],
            ['quote' => 'O NyxaACS resolveu o pesadelo de configurar CPE. O auto-provisionamento reduziu nossos chamados técnicos em 60%.', 'name' => 'Marina Silva', 'role' => 'Gerente de Operações — ConectaBR', 'initials' => 'MS'],
            ['quote' => 'A NyxaBot Layla atende 80% dos chamados no WhatsApp sozinha. O NyxaInsight mostrou que perdíamos dinheiro em 3 planos.', 'name' => 'Pedro Lima', 'role' => 'Diretor — VelozNet Internet', 'initials' => 'PL'],
        ],
        'contact' => [
            'whatsapp_number' => '5573999998888',
            'whatsapp_message' => 'Quero conhecer a NYXACORE',
            'email' => 'contato@nyxacore.com.br',
            'cta_title' => 'Pronto pra operar no próximo nível?',
            'cta_desc' => 'Agende uma demonstração gratuita e veja a suite NYXACORE rodando com os dados do seu provedor.',
        ],
        'seo' => [
            'title' => 'NYXACORE — Infraestrutura Inteligente para Provedores de Internet',
            'description' => 'Suite completa de software SaaS para ISPs: monitoramento, gestão de CPE, BI, mapas de rede, QoS, hotspot, chatbot IA.',
        ],
    ];
}

// ═══ Router ═══
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'content':
        if ($method === 'GET') {
            jsonResponse(readContent());
        }
        if ($method === 'POST' || $method === 'PUT') {
            if (getAuthToken() !== getAdminToken()) {
                jsonResponse(['error' => 'Token inválido'], 401);
            }
            $body = getRequestBody();
            if (empty($body)) {
                jsonResponse(['error' => 'JSON inválido'], 400);
            }
            writeContent($body);
            jsonResponse(['ok' => true]);
        }
        break;

    case 'login':
        if ($method === 'POST') {
            $body = getRequestBody();
            $token = $body['token'] ?? '';
            if ($token === getAdminToken()) {
                jsonResponse(['ok' => true, 'token' => getAdminToken()]);
            } else {
                jsonResponse(['ok' => false, 'error' => 'Token incorreto'], 401);
            }
        }
        break;

    case 'change_password':
        if ($method === 'POST') {
            if (getAuthToken() !== getAdminToken()) {
                jsonResponse(['error' => 'Não autorizado'], 401);
            }
            $body = getRequestBody();
            $currentToken = $body['current_token'] ?? '';
            $newToken = $body['new_token'] ?? '';
            if ($currentToken !== getAdminToken()) {
                jsonResponse(['ok' => false, 'error' => 'Senha atual incorreta'], 403);
            }
            if (strlen($newToken) < 8) {
                jsonResponse(['ok' => false, 'error' => 'Nova senha deve ter pelo menos 8 caracteres'], 400);
            }
            saveAdminToken($newToken);
            jsonResponse(['ok' => true, 'message' => 'Senha alterada com sucesso']);
        }
        break;

    default:
        jsonResponse(['error' => 'Ação não encontrada'], 404);
}
