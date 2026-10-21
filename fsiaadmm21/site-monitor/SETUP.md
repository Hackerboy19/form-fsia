# Site Monitor — Plesk setup (10 minute)

## 1. Password set karein
`fsiaadmm21/site-monitor/config.php` kholein aur badlein:
- `admin_password` → apna password (admin page kholne ke liye)
- `cron_key` → koi bhi lamba random text (sirf "Fetch a URL" wale tareeke mein chahiye)
- `alert_emails` → `// ` hata kar apna email daalein, taaki key page down / key file delete par turant email aaye

## 2. Upload
Plesk → Files → `httpdocs/fsiaadmm21/` ke andar poora `site-monitor` folder upload karein
(4 files: config.php, lib.php, cron.php, index.php).
Path aisa hona chahiye: `httpdocs/fsiaadmm21/site-monitor/index.php`

## 3. Har ghante ka cron (Plesk → Scheduled Tasks → Add Task)
- Task type: **Run a PHP script**
- Script path: `httpdocs/fsiaadmm21/site-monitor/cron.php`
- PHP version: jo site ka hai (8.x)
- Run: **Hourly** (ya Cron style: `0 * * * *`)
- Notify: "Errors only"
- **Run Now** dabayein → output mein "OK run #1" aana chahiye.

(Agar "Run a PHP script" na chale to: Task type **Fetch a URL** →
`https://www.fsia.in/fsiaadmm21/site-monitor/cron.php?key=AAPKA_CRON_KEY`)

## 4. Dekhna
`https://www.fsia.in/fsiaadmm21/site-monitor/` kholein, password daalein.
Pehla check sirf shuruaati list banata hai. Uske baad har ghante ke badlaav yahan dikhenge.

## Har ghante kya check hota hai
1. **Files**: httpdocs ki saari .php/.html/.xml/.txt/.js/.css/.htaccess files (uploads/images folder chhod kar).
   Koi file delete, nayi ya badli → entry (kab badli + size).
2. **Key files** (index.php, about.php, contact.php, sitemap.xml, robots.txt, .htaccess, config.php, header/footer, sitemap.php):
   delete hui to **backup se apne aap wapas lag jaati hai** + email alert.
   Baaki delete hui files admin page par "Wapas lagao" button se wapas lag jaati hain.
3. **Key pages**: /, about, contact, sitemap.xml, sitemap.php, robots.txt khol kar dekhta hai (200 aana chahiye, PHP Fatal error nahi).
4. **Profile URLs** (registration.url): URL badla, URL khali hua, profile delete hua, status/suspend badla.
5. **Sitemap links**: sitemap.php ke saare links, har ghante 200 links khol kar → poora sitemap ~1 din mein. Toote links list mein.

Backup copies yahan rehti hain (public nahi): `/var/www/vhosts/fsia.in/site-monitor-data/`
Purani entries 90 din baad apne aap hat jaati hain.
