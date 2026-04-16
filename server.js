/**
 * NYXACORE Landing — servidor mínimo com admin API.
 *
 * Serve arquivos estáticos + API JSON pra persistir conteúdo editável.
 * Roda: node server.js (porta default 3000, env PORT pra override)
 * Admin: /admin (protegido por ADMIN_TOKEN no env ou default 'nyxacore2026')
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = parseInt(process.env.PORT || '3000', 10);
const ADMIN_TOKEN = process.env.ADMIN_TOKEN || 'nyxacore2026';
const CONTENT_FILE = path.join(__dirname, 'content.json');

// ═══ Default content (usado quando content.json não existe) ═══
const DEFAULT_CONTENT = {
  hero: {
    badge: 'Sistema operacional para provedores',
    title_line1: 'Infraestrutura',
    title_accent: 'inteligente',
    title_line2: 'para quem conecta o Brasil.',
    description: 'Suite completa de software para ISPs — monitoramento, gestão de CPE, business intelligence, QoS, mapas de rede, notificações, chatbot IA e muito mais. Tudo integrado. Tudo sob controle.',
    cta_primary: 'Agendar demonstração',
    cta_secondary: 'Conhecer os produtos',
    stats: [
      { number: '9', label: 'Produtos integrados' },
      { number: '24/7', label: 'Monitoramento real-time' },
      { number: 'ML', label: 'Previsão inteligente' },
      { number: 'SaaS', label: 'Instalação em minutos' },
    ],
  },
  products: [
    { code: 'NW', name: 'NyxaWatch', desc: 'Monitoramento completo da rede — dashboards customizáveis, mapas interativos com Leaflet, NetFlow, detecção de anomalias com ML, alertas preditivos e 14 tipos de widgets.', tags: 'NMS · Zabbix Wrapper · Prophet ML' },
    { code: 'NA', name: 'NyxaACS', desc: 'Gestão TR-069 de CPEs com auto-provisionamento, autocura inteligente via IA, e integração com GenieACS. Configure milhares de roteadores em segundos.', tags: 'TR-069 · GenieACS · Autocura IA' },
    { code: 'NI', name: 'NyxaInsight', desc: 'Business Intelligence desenhado para ISPs. Conecta com HubSoft, IXC, SGP e MK-Auth. Painéis de churn, financeiro, comercial e geográfico.', tags: 'BI · Multi-ERP · Analytics' },
    { code: 'NM', name: 'NyxaMap', desc: 'Mapeamento de rede óptica — fusões, splitters, CTOs, cabos. Visualize toda a infraestrutura FTTH no mapa com viabilidade e documentação automática.', tags: 'FTTH · Mapeamento · GIS' },
    { code: 'NF', name: 'NyxaFlow', desc: 'Otimização de QoS e tráfego com CAKE, BBR e eBPF/XDP. Controle fino por assinante com DPI, detecção de DoS e relatórios de throughput em tempo real.', tags: 'QoS · eBPF · DPI · Traffic Shaping' },
    { code: 'NH', name: 'NyxaHotspot', desc: 'Hotspot centralizado multi-tenant com FreeRADIUS e MikroTik. Portal captivo personalizável, vouchers, relatórios de uso e controle por ISP.', tags: 'WiFi · FreeRADIUS · Multi-tenant' },
    { code: 'NB', name: 'NyxaBot', desc: 'Atendente IA multi-canal — WhatsApp, Telegram e integração direta com HubSoft. Persona "Layla" resolve dúvidas, abre OS e consulta faturas automaticamente.', tags: 'IA · WhatsApp · HubSoft · Layla' },
    { code: 'NO', name: 'NyxaOrion', desc: 'Provisionamento e automação de rede — orquestra configurações, deploys e atualizações em larga escala com workflows visuais.', tags: 'Provisioning · Automation · Workflows' },
    { code: 'NX', name: 'NyxaFlix', desc: 'Plataforma de cursos no formato Netflix — área de alunos, certificados, progresso, streaming de vídeo. Ideal para treinamento de equipe e clientes.', tags: 'E-Learning · Streaming · Certificados' },
  ],
  pricing: [
    {
      name: 'Starter', price: 'R$ 297', period: '/mês', note: 'Até 500 dispositivos monitorados', featured: false,
      features: ['NyxaWatch (monitoramento completo)', 'Dashboards customizáveis', 'Mapas de rede canvas', 'Alertas por Telegram + Email', '1 site (POP único)', 'Suporte por email'],
      cta: 'Começar trial grátis',
    },
    {
      name: 'Professional', price: 'R$ 697', period: '/mês', note: 'Até 5.000 dispositivos', featured: true,
      features: ['Tudo do Starter +', 'NyxaACS (gestão TR-069)', 'NyxaInsight (BI)', 'NetFlow + detecção de anomalias', 'WhatsApp + Webhook + SMS', 'Multi-site (até 10 sites)', 'ML forecasting básico', 'Suporte prioritário'],
      cta: 'Começar trial grátis',
    },
    {
      name: 'Enterprise', price: 'Sob consulta', period: '', note: 'Dispositivos ilimitados', featured: false,
      features: ['Tudo do Professional +', 'Todos os 9 produtos', 'Sites ilimitados + federação', 'ML avançado + AI Insights', 'Integração ERP customizada', 'SLA dedicado + gerente de conta', 'Treinamento presencial', 'Instalação assistida'],
      cta: 'Falar com especialista',
    },
  ],
  testimonials: [
    { quote: 'Antes a gente levava horas pra descobrir onde o problema tava. Com o NyxaWatch, o sistema avisa antes do cliente ligar reclamando. O ML previu a saturação do uplink com 3 semanas de antecedência.', name: 'Ricardo Costa', role: 'CTO — FibraNet Telecom', initials: 'RC' },
    { quote: 'O NyxaACS resolveu o pesadelo de configurar CPE. O auto-provisionamento com autocura de IA reduziu nossos chamados técnicos em 60%. A equipe finalmente consegue focar no que importa.', name: 'Marina Silva', role: 'Gerente de Operações — ConectaBR', initials: 'MS' },
    { quote: 'A NyxaBot Layla atende 80% dos chamados no WhatsApp sozinha. Os clientes adoram a velocidade. E o NyxaInsight mostrou que estávamos perdendo dinheiro em 3 planos que ninguém usava.', name: 'Pedro Lima', role: 'Diretor — VelozNet Internet', initials: 'PL' },
  ],
  contact: {
    whatsapp_number: '5573999998888',
    whatsapp_message: 'Quero conhecer a NYXACORE',
    email: 'contato@nyxacore.com.br',
    cta_title: 'Pronto pra operar no próximo nível?',
    cta_desc: 'Agende uma demonstração gratuita e veja a suite NYXACORE rodando com os dados do seu provedor. Sem compromisso.',
  },
  seo: {
    title: 'NYXACORE — Infraestrutura Inteligente para Provedores de Internet',
    description: 'Suite completa de software SaaS para ISPs: monitoramento, gestão de CPE, BI, mapas de rede, QoS, hotspot, chatbot IA, e mais.',
  },
};

// ═══ Read/write content ═══
function readContent() {
  try {
    if (fs.existsSync(CONTENT_FILE)) {
      return JSON.parse(fs.readFileSync(CONTENT_FILE, 'utf-8'));
    }
  } catch (e) {
    console.warn('content.json parse error, usando default');
  }
  return DEFAULT_CONTENT;
}

function writeContent(data) {
  fs.writeFileSync(CONTENT_FILE, JSON.stringify(data, null, 2), 'utf-8');
}

// ═══ MIME types ═══
const MIME = {
  '.html': 'text/html', '.css': 'text/css', '.js': 'application/javascript',
  '.json': 'application/json', '.png': 'image/png', '.jpg': 'image/jpeg',
  '.svg': 'image/svg+xml', '.ico': 'image/x-icon', '.woff2': 'font/woff2',
};

// ═══ Auth check ═══
function checkAuth(req) {
  const auth = req.headers['authorization'];
  if (auth && auth.startsWith('Bearer ')) {
    return auth.slice(7) === ADMIN_TOKEN;
  }
  const parsed = url.parse(req.url, true);
  return parsed.query.token === ADMIN_TOKEN;
}

// ═══ Server ═══
const server = http.createServer((req, res) => {
  const parsed = url.parse(req.url, true);
  const pathname = parsed.pathname;

  // CORS
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  if (req.method === 'OPTIONS') { res.writeHead(204); res.end(); return; }

  // API: GET content
  if (pathname === '/api/content' && req.method === 'GET') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(readContent()));
    return;
  }

  // API: PUT content (protected)
  if (pathname === '/api/content' && req.method === 'PUT') {
    if (!checkAuth(req)) {
      res.writeHead(401, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ error: 'Token inválido' }));
      return;
    }
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      try {
        const data = JSON.parse(body);
        writeContent(data);
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ ok: true }));
      } catch (e) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'JSON inválido' }));
      }
    });
    return;
  }

  // API: POST login (validate token)
  if (pathname === '/api/login' && req.method === 'POST') {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      try {
        const { token } = JSON.parse(body);
        if (token === ADMIN_TOKEN) {
          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ ok: true, token: ADMIN_TOKEN }));
        } else {
          res.writeHead(401, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ ok: false, error: 'Token incorreto' }));
        }
      } catch (e) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'JSON inválido' }));
      }
    });
    return;
  }

  // Static files
  let filePath = pathname === '/' ? '/index.html' : pathname;
  filePath = path.join(__dirname, filePath);

  // Prevent path traversal
  if (!filePath.startsWith(__dirname)) {
    res.writeHead(403); res.end('Forbidden'); return;
  }

  fs.readFile(filePath, (err, data) => {
    if (err) {
      res.writeHead(404, { 'Content-Type': 'text/html' });
      res.end('<h1>404</h1>');
      return;
    }
    const ext = path.extname(filePath);
    res.writeHead(200, { 'Content-Type': MIME[ext] || 'application/octet-stream' });
    res.end(data);
  });
});

server.listen(PORT, () => {
  console.log(`NYXACORE Landing rodando em http://localhost:${PORT}`);
  console.log(`Admin: http://localhost:${PORT}/admin.html`);
  console.log(`Token: ${ADMIN_TOKEN === 'nyxacore2026' ? 'nyxacore2026 (TROQUE em produção via env ADMIN_TOKEN)' : '***'}`);
});
