import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const envPath = path.join(root, '.env');
const outDir = path.join(root, 'storage', 'app', 'playwright');

function readEnv(key) {
    if (!fs.existsSync(envPath)) {
        return '';
    }

    const line = fs.readFileSync(envPath, 'utf8').split(/\r?\n/).find((row) => row.startsWith(`${key}=`));
    if (!line) {
        return '';
    }

    return line.slice(key.length + 1).trim().replace(/^["']|["']$/g, '');
}

const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000';
const email = readEnv('ADMIN_EMAIL');
const password = readEnv('ADMIN_PASSWORD');

const viewports = [
    { name: 'iphone', width: 390, height: 844 },
    { name: 'fold', width: 344, height: 882 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'tablet-landscape', width: 1024, height: 768 },
    { name: 'desktop', width: 1440, height: 900 },
];

const pages = [
    { name: 'login', path: '/login', auth: false },
    { name: 'dashboard', path: '/admin', auth: true },
    { name: 'events', path: '/admin/events', auth: true },
    { name: 'event-create', path: '/admin/events/create', auth: true },
    { name: 'participants', path: '/admin/participants', auth: true },
    { name: 'study-programs', path: '/admin/study-programs', auth: true },
    { name: 'categories', path: '/admin/categories', auth: true },
    { name: 'recipients-pejabat', path: '/admin/recipients/pejabat', auth: true },
    { name: 'monitoring', path: '/monitoring/mahasiswa', auth: true },
    { name: 'checkin', path: '/admin/checkin/manual', auth: true },
    { name: 'scanner', path: '/admin/checkin/scanner', auth: true },
];

async function overflow(page) {
    return page.evaluate(() => {
        const doc = document.documentElement;
        return {
            scrollWidth: doc.scrollWidth,
            innerWidth: window.innerWidth,
            overflow: Math.max(0, doc.scrollWidth - window.innerWidth),
        };
    });
}

async function shellGeometry(page) {
    return page.evaluate(() => {
        const sidebar = document.querySelector('#left-sidebar.sidebar');
        const navbar = document.querySelector('.navbar.navbar-fixed-top');
        const main = document.querySelector('#main-content');
        const scrim = document.querySelector('.admin-scrim');

        if (!sidebar || !navbar || !main) {
            return null;
        }

        const sidebarBox = sidebar.getBoundingClientRect();
        const navbarBox = navbar.getBoundingClientRect();
        const mainBox = main.getBoundingClientRect();
        const scrimStyle = scrim ? getComputedStyle(scrim) : null;

        return {
            innerWidth: window.innerWidth,
            sidebarLeft: Math.round(sidebarBox.left),
            navbarLeft: Math.round(navbarBox.left),
            navbarRight: Math.round(navbarBox.right),
            navbarBottom: Math.round(navbarBox.bottom),
            mainTop: Math.round(mainBox.top),
            mainRight: Math.round(mainBox.right),
            scrimVisible: Boolean(
                scrimStyle
                && scrimStyle.visibility !== 'hidden'
                && Number.parseFloat(scrimStyle.opacity) > 0.05
            ),
        };
    });
}

async function main() {
    fs.mkdirSync(outDir, { recursive: true });

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();
    const failures = [];
    let authed = false;

    if (email && password) {
        await page.goto(`${baseURL}/login`, { waitUntil: 'networkidle' });
        await page.fill('#signin-email, input[name="email"]', email);
        await page.fill('#signin-password, input[name="password"]', password);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => null),
            page.click('button[type="submit"], .login-button'),
        ]);
        authed = page.url().includes('/admin') || page.url().includes('/monitoring') || !page.url().includes('/login');
        if (!authed) {
            failures.push('Login admin gagal. Cek ADMIN_EMAIL / ADMIN_PASSWORD.');
        }
    } else {
        failures.push('ADMIN_EMAIL / ADMIN_PASSWORD kosong, halaman auth dilewati.');
    }

    for (const viewport of viewports) {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });

        for (const route of pages) {
            if (route.auth && !authed) {
                continue;
            }

            const response = await page.goto(`${baseURL}${route.path}`, { waitUntil: 'domcontentloaded' });
            await page.waitForTimeout(350);
            await page.evaluate(() => window.scrollTo(0, 0));
            const status = response?.status() ?? 0;
            const box = await overflow(page);
            const geo = route.auth ? await shellGeometry(page) : null;
            const shot = path.join(outDir, `${viewport.name}-${route.name}.png`);
            await page.screenshot({ path: shot, fullPage: false });

            if (status >= 400) {
                failures.push(`${viewport.name} ${route.path} status ${status}`);
            }

            if (box.overflow > 8) {
                failures.push(`${viewport.name} ${route.path} overflow ${box.overflow}px`);
            }

            if (geo) {
                    if (geo.scrimVisible) {
                        failures.push(`${viewport.name} ${route.path} scrim covering shell`);
                    }
                    if (geo.mainTop < geo.navbarBottom - 1) {
                        failures.push(`${viewport.name} ${route.path} content collides navbar (${geo.mainTop} < ${geo.navbarBottom})`);
                    }
                    if (viewport.width >= 1280) {
                        if (geo.sidebarLeft > 2) {
                            failures.push(`${viewport.name} ${route.path} sidebar not flush left (${geo.sidebarLeft}px)`);
                        }
                        if (Math.abs(geo.navbarLeft - 280) > 4) {
                            failures.push(`${viewport.name} ${route.path} navbar left ${geo.navbarLeft}px`);
                        }
                        if (geo.innerWidth - geo.navbarRight > 4) {
                            failures.push(`${viewport.name} ${route.path} navbar right gap ${geo.innerWidth - geo.navbarRight}px`);
                        }
                        if (geo.innerWidth - geo.mainRight > 8) {
                            failures.push(`${viewport.name} ${route.path} content right gap ${geo.innerWidth - geo.mainRight}px`);
                        }
                    } else if (geo.navbarLeft > 2) {
                        failures.push(`${viewport.name} ${route.path} mobile navbar left ${geo.navbarLeft}px`);
                    }
            }
        }
    }

    await browser.close();

    const report = path.join(outDir, 'report.json');
    fs.writeFileSync(report, JSON.stringify({ baseURL, authed, failures, outDir }, null, 2));

    if (failures.length) {
        console.error(failures.join('\n'));
        process.exit(1);
    }

    console.log(`Admin responsive OK. Screenshots: ${outDir}`);
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
