# TechSpace — Document Management System

A PHP + MySQL web app for your domain **techspace.ifree.page**.

## What it does
- **Admin panel** — upload `.docx` (Word) and `.txt` files → content parsed and stored in MySQL
- **User panel** — public-facing page showing all documents, searchable and filterable by category

---

## File Structure

```
techspace/
├── index.php                ← Public user panel (document list)
├── view.php                 ← Read a single document
├── admin/
│   ├── login.php            ← Admin login
│   ├── logout.php           ← Clears session
│   ├── index.php            ← Admin dashboard (all docs + stats)
│   ├── upload.php           ← Upload Word/text files
│   └── delete.php           ← Delete a document
├── config/
│   ├── db.php               ← ⭐ Enter your DB credentials here
│   └── session.php          ← Session & auth helpers
├── includes/
│   ├── header.php           ← Shared header
│   ├── footer.php           ← Shared footer
│   └── file-parser.php      ← .docx / .txt text extractor
├── db/
│   ├── setup.sql            ← Run this in phpMyAdmin first!
│   └── create-admin.php     ← Change admin password (delete after use)
└── assets/
    ├── css/style.css
    └── js/main.js
```

---

## Setup Guide (InfinityFree + techspace.ifree.page)

### Step 1 — Create a MySQL Database

1. Log in to **InfinityFree cPanel** → **MySQL Databases**
2. Create a new database, e.g. `if0_XXXXXXX_techspace`
3. Create a user and assign it to the database (All Privileges)
4. Note down: **host**, **database name**, **username**, **password**

### Step 2 — Edit `config/db.php`

Open the file and replace these lines:

```php
define('DB_HOST', 'sql.infinityfree.com');   // from cPanel
define('DB_NAME', 'if0_XXXXXXX_techspace');  // your DB name
define('DB_USER', 'if0_XXXXXXX');            // your DB username
define('DB_PASS', 'your_password');          // your DB password
```

> The exact MySQL hostname is shown in your InfinityFree cPanel under "MySQL Databases"

### Step 3 — Run the SQL setup

1. Open **phpMyAdmin** (from cPanel → MySQL Databases → phpMyAdmin)
2. Select your database from the left sidebar
3. Click the **SQL** tab
4. Paste the entire content of `db/setup.sql` and click **Go**

This creates:
- `documents` table — stores all uploaded file content
- `admin_users` table — stores admin login credentials

Default admin login:
- **Username:** `admin`
- **Password:** `Admin@1234`

### Step 4 — Change Admin Password

1. Upload all files to `htdocs/` on InfinityFree
2. Visit: `https://techspace.ifree.page/db/create-admin.php`
3. Set a new strong password
4. **Delete `db/create-admin.php` from your server immediately!**

### Step 5 — Upload files via File Manager

1. In InfinityFree cPanel → **File Manager** → `htdocs/`
2. Upload all files from this folder (keep the folder structure)
3. Visit `https://techspace.ifree.page` — you're live!

---

## Using the App

### Admin Panel
- Go to `https://techspace.ifree.page/admin/login.php`
- Login with your credentials
- **Upload** → pick a `.docx` or `.txt` file, set title/category/description → Submit
- The file content is extracted and stored in MySQL (no files saved on disk)
- **Delete** documents from the dashboard table

### User Panel
- Visit `https://techspace.ifree.page`
- All uploaded documents appear as cards
- Users can search by keyword or filter by category
- Click **Read More** to read the full document content

---

## Supported File Types

| Format | Extension | Notes |
|--------|-----------|-------|
| Word   | `.docx`   | Text extracted from XML inside the file. Formatting/images not preserved — text only. |
| Plain text | `.txt` | Read as-is |

> `.doc` (old Word format) is **not** supported — save as `.docx` first.

---

## Security Notes

- Admin passwords are hashed with `password_hash()` (bcrypt)
- All output is escaped with `htmlspecialchars()`
- CSRF tokens protect all POST forms
- Sessions are hardened with `httponly` and `samesite=Strict`
- Delete `db/create-admin.php` after first use
