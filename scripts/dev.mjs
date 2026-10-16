import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { randomBytes } from 'node:crypto';
import { runCLI } from '@wp-playground/cli';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
process.chdir(root);
const runtime = resolve(root, '.runtime');
const wordpress = resolve(runtime, 'wordpress');
mkdirSync(wordpress, { recursive: true });
const accessPath = resolve(runtime, 'acesso-local.json');
if (!existsSync(accessPath)) {
  writeFileSync(accessPath, JSON.stringify({ usuario: 'admin', senha: randomBytes(18).toString('base64url') }, null, 2));
}
const access = JSON.parse(readFileSync(accessPath, 'utf8'));
const port = Number(process.env.PORT || 8080);
if (!Number.isInteger(port) || port < 1024 || port > 65535) throw new Error('Porta local inválida.');
const url = `http://localhost:${port}`;
const blueprint = {
  landingPage: '/',
  steps: [
    { step: 'runPHP', code: "<?php require '/fort-tools/validate.php'; fort_validate_php_sources();" },
    { step: 'activatePlugin', pluginPath: 'fort-catalogo/fort-catalogo.php' },
    { step: 'activateTheme', themeFolderName: 'fort-metais' },
    { step: 'runPHP', code: `<?php require '/wordpress/wp-load.php'; require '/fort-tools/seed.php'; fort_seed_local(); $admin = get_user_by('login', 'admin'); if ($admin && !wp_check_password(${JSON.stringify(access.senha)}, $admin->user_pass, $admin->ID)) wp_set_password(${JSON.stringify(access.senha)}, $admin->ID);` },
    ...(!existsSync(resolve(wordpress, 'wp-content/languages/pt_BR.mo')) ? [{ step: 'setSiteLanguage', language: 'pt_BR' }] : []),
  ],
};

console.log('Preparando o WordPress da Fort Metais…');
const local = await runCLI({
  command: 'server', port, 'site-url': url, php: '8.3', wp: 'latest', login: false, workers: 1,
  wordpressInstallMode: existsSync(resolve(wordpress, 'wp-load.php')) ? 'install-from-existing-files-if-needed' : 'download-and-install',
  'mount-before-install': [
    { hostPath: wordpress, vfsPath: '/wordpress' },
    { hostPath: resolve(root, 'wp-content/themes/fort-metais'), vfsPath: '/wordpress/wp-content/themes/fort-metais' },
    { hostPath: resolve(root, 'wp-content/plugins/fort-catalogo'), vfsPath: '/wordpress/wp-content/plugins/fort-catalogo' },
    { hostPath: resolve(root, 'scripts'), vfsPath: '/fort-tools' },
  ],
  'define-bool': { WP_DEBUG: true, WP_DEBUG_LOG: true, WP_DEBUG_DISPLAY: false, DISALLOW_FILE_EDIT: true },
  define: { WP_ENVIRONMENT_TYPE: 'local' },
  blueprint,
});

// The upstream CLI binds all interfaces. Rebind its server to this computer only.
await new Promise((done, reject) => local.server.close(error => error ? reject(error) : done()));
await new Promise((done, reject) => local.server.listen(port, '127.0.0.1', done).once('error', reject));
writeFileSync(resolve(runtime, 'servidor.json'), JSON.stringify({ pid: process.pid, url, painel: `${url}/wp-admin/` }, null, 2));
console.log(`\nSite: ${url}\nPainel: ${url}/wp-admin/\nAcesso alternativo: .runtime/acesso-local.json\nDados persistentes: .runtime/wordpress\n`);

let stopping = false;
async function stop() {
  if (stopping) return;
  stopping = true;
  await local[Symbol.asyncDispose]();
  process.exit(0);
}
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
